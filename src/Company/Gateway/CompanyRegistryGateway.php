<?php

namespace App\Company\Gateway;

use App\Company\Helper\SiretHelper;
use App\Company\Model\RegistryEstablishment;
use NeoPHP\Component\HttpClient\HttpClientManagerInterface;
use Throwable;

class CompanyRegistryGateway
{
    public const TIMEOUT = 5;

    private const ENDPOINT = 'https://recherche-entreprises.api.gouv.fr/search';

    private const ACTIVE = 'A';

    public function __construct(
        private HttpClientManagerInterface $http,
    ) {
    }

    public function findBySiret(string $siret): ?RegistryEstablishment
    {
        $siret = SiretHelper::normalize($siret);

        foreach ($this->search($siret) as $company) {
            foreach ($this->establishments($company) as $establishment) {
                if (($establishment['siret'] ?? null) === $siret) {
                    return $this->map($company, $establishment);
                }
            }
        }

        return null;
    }

    private function search(string $siret): array
    {
        try {
            $response = $this->http->get(self::ENDPOINT, [
                'query' => [
                    'q' => $siret,
                    'per_page' => 1
                ],
                'timeout' => self::TIMEOUT,
            ]);

            if (!$response->isSuccessful()) {
                return [];
            }

            return $response->toArray(false)['results'] ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    private function establishments(array $company): array
    {
        return [
            ...($company['matching_etablissements'] ?? []),
            $company['siege'] ?? []
        ];
    }

    private function map(array $company, array $establishment): RegistryEstablishment
    {
        return new RegistryEstablishment(
            siret: $establishment['siret'],
            siren: (string) ($company['siren'] ?? SiretHelper::sirenOf($establishment['siret'])),
            legalName: (string) ($company['nom_raison_sociale'] ?? $company['nom_complet'] ?? ''),
            brandName: $establishment['liste_enseignes'][0] ?? null,
            address: $establishment['adresse'] ?? null,
            postalCode: $establishment['code_postal'] ?? null,
            locality: $establishment['libelle_commune'] ?? null,
            apeCode: $establishment['activite_principale'] ?? $company['activite_principale'] ?? null,
            apeLabel: $company['libelle_activite_principale'] ?? null,
            active: ($establishment['etat_administratif'] ?? self::ACTIVE) === self::ACTIVE,
            latitude: $this->coordinate($establishment['latitude'] ?? null),
            longitude: $this->coordinate($establishment['longitude'] ?? null),
        );
    }

    private function coordinate(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }
}