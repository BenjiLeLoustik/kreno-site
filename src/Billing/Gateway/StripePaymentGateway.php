<?php

namespace App\Billing\Gateway;

use App\Billing\Enum\PaymentMethodType;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Exception\PaymentGatewayException;
use App\Billing\Model\GatewayPaymentMethod;
use App\Billing\Model\GatewayPaymentResult;
use App\Billing\Model\GatewaySetup;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyPaymentMethod;
use App\Shared\Exception\BusinessRuleException;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\HttpClient\HttpClientManagerInterface;
use Throwable;

class StripePaymentGateway implements PaymentGatewayInterface
{
    public const API_URL = 'https://api.stripe.com/v1/';

    public const WEBHOOK_TOLERANCE = 300;

    public const DECLINE_MESSAGES = [
        'insufficient_funds' => 'provision insuffisante',
        'expired_card' => 'carte expirée',
        'card_declined' => 'paiement refusé par la banque',
        'authentication_required' => 'authentification 3D Secure non validée',
        'incorrect_cvc' => 'code de sécurité incorrect',
        'processing_error' => 'erreur de traitement chez la banque',
        'card_velocity_exceeded' => 'plafond de paiement atteint',
    ];

    public function __construct(
        private HttpClientManagerInterface $http,
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        private string $secretKey,
        #[Autowire(env: 'STRIPE_PUBLIC_KEY')]
        private string $publicKey,
        #[Autowire(env: 'STRIPE_WEBHOOK_SECRET')]
        private string $webhookSecret,
    ) {
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function createSetup(Company $company, PaymentMethodType $type, ?CompanyPaymentMethod $existing = null): GatewaySetup
    {
        $customer = $existing !== null ? $this->customerOf((string) $existing->getProviderReference()) : null;
        $customer ??= $this->request('POST', 'customers', [
            'name' => $company->getLegalName() ?? $company->getName(),
            'email' => $company->getConfig()?->getBillingEmail(),
            'metadata' => ['company_id' => $company->getId()],
        ])['id'];

        $intent = $this->request('POST', 'setup_intents', [
            'customer' => $customer,
            'usage' => 'off_session',
            'automatic_payment_methods' => ['enabled' => 'true', 'allow_redirects' => 'never'],
            'metadata' => ['company_id' => $company->getId(), 'type' => $type->value],
        ]);

        return new GatewaySetup((string) $intent['id'], (string) $intent['client_secret']);
    }

    public function completeSetup(string $setupReference): GatewayPaymentMethod
    {
        $intent = $this->request('GET', 'setup_intents/' . rawurlencode($setupReference), ['expand' => ['payment_method']]);

        if (($intent['status'] ?? null) !== 'succeeded' || !is_array($intent['payment_method'] ?? null)) {
            throw new BusinessRuleException('Votre banque n\'a pas validé l\'enregistrement de ce moyen de paiement.');
        }

        $method = $intent['payment_method'];
        $type = PaymentMethodType::tryFrom((string) $method['type']) ?? throw new BusinessRuleException('Ce moyen de paiement n\'est pas accepté.');

        if ($type === PaymentMethodType::CARD) {
            return new GatewayPaymentMethod(
                (string) $method['id'],
                $type,
                isset($method['card']['brand']) ? ucfirst((string) $method['card']['brand']) : null,
                $method['card']['last4'] ?? null,
                isset($method['card']['exp_month']) ? (int) $method['card']['exp_month'] : null,
                isset($method['card']['exp_year']) ? (int) $method['card']['exp_year'] : null,
                $method['billing_details']['name'] ?? null,
            );
        }

        return new GatewayPaymentMethod(
            (string) $method['id'],
            $type,
            'SEPA',
            $method['sepa_debit']['last4'] ?? null,
            null,
            null,
            $method['billing_details']['name'] ?? null,
            is_string($intent['mandate'] ?? null) ? $intent['mandate'] : null,
        );
    }

    public function charge(CompanyPaymentMethod $method, int $amount, string $description, string $idempotencyKey, bool $offSession): GatewayPaymentResult
    {
        $reference = (string) $method->getProviderReference();
        $parameters = [
            'amount' => $amount,
            'currency' => 'eur',
            'customer' => $this->customerOf($reference),
            'payment_method' => $reference,
            'automatic_payment_methods' => ['enabled' => 'true', 'allow_redirects' => 'never'],
            'confirm' => 'true',
            'description' => $description,
            'metadata' => ['type' => $method->getType()?->value ?? PaymentMethodType::CARD->value],
        ];

        if ($offSession) {
            $parameters['off_session'] = 'true';
        }

        [$successful, $data] = $this->send('POST', 'payment_intents', $parameters, ['Idempotency-Key' => $idempotencyKey]);

        if ($successful) {
            return $this->toResult($data);
        }

        $error = is_array($data['error'] ?? null) ? $data['error'] : [];

        if (($error['type'] ?? null) !== 'card_error') {
            throw new PaymentGatewayException(sprintf('Le prestataire de paiement a refusé la requête : %s', $error['message'] ?? 'erreur inconnue'));
        }

        if (is_array($error['payment_intent'] ?? null)) {
            return $this->toResult($error['payment_intent'] + ['last_payment_error' => $error]);
        }

        $code = $error['decline_code'] ?? $error['code'] ?? 'card_declined';

        return new GatewayPaymentResult(null, PaymentStatus::FAILED, null, $code, self::DECLINE_MESSAGES[$code] ?? self::DECLINE_MESSAGES['card_declined']);
    }

    public function retrieveCharge(string $reference): GatewayPaymentResult
    {
        return $this->toResult($this->request('GET', 'payment_intents/' . rawurlencode($reference)));
    }

    public function detach(CompanyPaymentMethod $method): void
    {
        if ($method->getProviderReference() !== null) {
            $this->request('POST', sprintf('payment_methods/%s/detach', rawurlencode($method->getProviderReference())));
        }
    }

    public function parseWebhook(string $payload, string $signature): ?GatewayPaymentResult
    {
        $parts = [];

        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key][] = $value;
        }

        $timestamp = (int) ($parts['t'][0] ?? 0);
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $this->webhookSecret);
        $valid = array_filter($parts['v1'] ?? [], static fn (string $candidate): bool => hash_equals($expected, $candidate));

        if ($valid === [] || abs(time() - $timestamp) > self::WEBHOOK_TOLERANCE) {
            throw new BusinessRuleException('Signature de notification invalide.');
        }

        $event = json_decode($payload, true);

        if (!is_array($event) || !str_starts_with((string) ($event['type'] ?? ''), 'payment_intent.') || !is_array($event['data']['object'] ?? null)) {
            return null;
        }

        return $this->toResult($event['data']['object']);
    }

    private function customerOf(string $paymentMethodReference): ?string
    {
        $method = $this->request('GET', 'payment_methods/' . rawurlencode($paymentMethodReference));

        return is_string($method['customer'] ?? null) ? $method['customer'] : null;
    }

    private function toResult(array $intent): GatewayPaymentResult
    {
        $error = is_array($intent['last_payment_error'] ?? null) ? $intent['last_payment_error'] : [];
        $code = $error['decline_code'] ?? $error['code'] ?? null;
        $status = match ($intent['status'] ?? null) {
            'succeeded' => PaymentStatus::SUCCEEDED,
            'processing' => PaymentStatus::PENDING,
            'requires_action', 'requires_confirmation' => PaymentStatus::REQUIRES_ACTION,
            'canceled' => PaymentStatus::CANCELED,
            default => PaymentStatus::FAILED,
        };

        return new GatewayPaymentResult(
            isset($intent['id']) ? (string) $intent['id'] : null,
            $status,
            $status === PaymentStatus::REQUIRES_ACTION ? ($intent['client_secret'] ?? null) : null,
            $status === PaymentStatus::FAILED ? ($code ?? 'card_declined') : null,
            $status === PaymentStatus::FAILED ? (self::DECLINE_MESSAGES[$code ?? 'card_declined'] ?? self::DECLINE_MESSAGES['card_declined']) : null,
        );
    }

    private function request(string $method, string $path, array $parameters = [], array $headers = []): array
    {
        [$successful, $data] = $this->send($method, $path, $parameters, $headers);

        if ($successful) {
            return $data;
        }

        $error = is_array($data['error'] ?? null) ? $data['error'] : [];

        if (($error['type'] ?? null) === 'card_error') {
            throw new BusinessRuleException(self::DECLINE_MESSAGES[$error['decline_code'] ?? $error['code'] ?? 'card_declined'] ?? self::DECLINE_MESSAGES['card_declined']);
        }

        throw new PaymentGatewayException(sprintf('Le prestataire de paiement a refusé la requête : %s', $error['message'] ?? 'erreur inconnue'));
    }

    private function send(string $method, string $path, array $parameters, array $headers): array
    {
        try {
            $options = ['auth_bearer' => $this->secretKey, 'timeout' => 20, 'headers' => $headers];
            $options[$method === 'GET' ? 'query' : 'body'] = $parameters;
            $response = $this->http->request($method, self::API_URL . $path, $options);

            return [$response->isSuccessful(), $response->toArray(false)];
        } catch (Throwable $exception) {
            throw new PaymentGatewayException(sprintf('Le prestataire de paiement est injoignable : %s', $exception->getMessage()));
        }
    }
}