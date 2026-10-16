<?php

namespace App\Website\Service;

use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsiteRule;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Dto\WebsiteRuleInput;
use NeoPHP\Package\Orm\OrmManagerInterface;

class WebsiteRuleService
{
    public const LABELS = [
        'freeCancellationMinutes' => ['Annulation gratuite', 'min avant'],
        'bookingLeadMinutes' => ['Délai minimal de réservation', 'min avant'],
        'maxUpcomingBookingsPerUser' => ['Réservations en cours par client', 'max'],
        'toleratedNoShows' => ['Absences tolérées', 'absences'],
        'noShowWindowDays' => ['Période comptée', 'jours'],
        'suspensionDays' => ['Durée de suspension', 'jours'],
        'minDiscount' => ['Remise minimale', '%'],
        'maxDiscount' => ['Remise maximale', '%'],
        'defaultDiscount' => ['Remise par défaut', '%'],
        'publicationWindowDays' => ['Publication à l\'avance', 'jours'],
        'autoRemovalMinutes' => ['Retrait automatique', 'min avant'],
    ];

    public function __construct(
        private OrmManagerInterface $orm,
        private WebsiteVersionService $versions,
        private ActivityLogger $logger,
    ) {
    }

    public function update(WebsiteConfig $website, WebsiteRuleInput $input, ?User $actor = null): WebsiteRule
    {
        $this->versions->assertEditable($website);

        $rule = $website->getRule() ?? (new WebsiteRule())->setWebsite($website);
        $values = array_merge($this->toArray($rule), $input->toArray());

        $this->assertConsistent($values);

        foreach ($values as $key => $value) {
            $current = $rule->{'get' . ucfirst($key)}();

            if ($current === $value) {
                continue;
            }

            $rule->{'set' . ucfirst($key)}($value);
            [$label, $unit] = self::LABELS[$key];
            $this->logger->log(ActivityType::RULES, sprintf('%s : %d → %d %s', $label, $current, $value, $unit), $actor, $website, $rule, [$key => ['from' => $current, 'to' => $value]]);
        }

        $this->orm->persist($rule);
        $this->orm->flush();

        return $rule;
    }

    public function toArray(WebsiteRule $rule): array
    {
        $values = [];

        foreach (array_keys(self::LABELS) as $key) {
            $values[$key] = $rule->{'get' . ucfirst($key)}();
        }

        return $values;
    }

    private function assertConsistent(array $values): void
    {
        $errors = [];

        if ($values['minDiscount'] >= $values['maxDiscount']) {
            $errors[] = 'la remise minimale doit être inférieure à la maximale';
        }

        if ($values['defaultDiscount'] < $values['minDiscount'] || $values['defaultDiscount'] > $values['maxDiscount']) {
            $errors[] = 'la remise par défaut doit être comprise entre la minimale et la maximale';
        }

        if ($values['autoRemovalMinutes'] >= $values['freeCancellationMinutes']) {
            $errors[] = 'le retrait automatique doit arriver avant la fin de l\'annulation gratuite';
        }

        if ($errors !== []) {
            throw new BusinessRuleException('Règles invalides : {errors}.', ['errors' => implode(' ; ', $errors)]);
        }
    }
}