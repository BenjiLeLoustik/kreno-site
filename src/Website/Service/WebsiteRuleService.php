<?php

namespace App\Website\Service;

use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsiteRule;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Website\Dto\WebsiteRuleInput;
use App\Website\Enum\RuleField;
use App\Website\Exception\InvalidRulesException;
use NeoPHP\Package\Orm\OrmManagerInterface;

class WebsiteRuleService
{
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
            $field = RuleField::from($key);
            $current = $rule->{$field->getter()}();

            if ($current === $value) {
                continue;
            }

            $rule->{$field->setter()}($value);
            $this->logChange($website, $rule, $field, $current, $value, $actor);
        }

        $this->orm->persist($rule);
        $this->orm->flush();

        return $rule;
    }

    public function toArray(WebsiteRule $rule): array
    {
        $values = [];

        foreach (RuleField::cases() as $field) {
            $values[$field->value] = $rule->{$field->getter()}();
        }

        return $values;
    }

    private function logChange(
        WebsiteConfig $website,
        WebsiteRule $rule,
        RuleField $field,
        ?int $current,
        int $value,
        ?User $actor,
    ): void {
        $message = sprintf('%s : %d → %d %s', $field->label(), $current, $value, $field->unit());
        $context = [
            $field->value => [
                'from' => $current,
                'to' => $value
            ]
        ];

        $this->logger->log(ActivityType::RULES, $message, $actor, $website, $rule, $context);
    }

    private function assertConsistent(array $values): void
    {
        $errors = [];

        if ($values['minDiscount'] >= $values['maxDiscount']) {
            $errors[] = InvalidRulesException::MIN_ABOVE_MAX;
        }

        $default = $values['defaultDiscount'];

        if ($default < $values['minDiscount'] || $default > $values['maxDiscount']) {
            $errors[] = InvalidRulesException::DEFAULT_OUT_OF_RANGE;
        }

        if ($values['autoRemovalMinutes'] >= $values['freeCancellationMinutes']) {
            $errors[] = InvalidRulesException::AUTO_REMOVAL_TOO_LATE;
        }

        if ($errors !== []) {
            throw new InvalidRulesException($errors);
        }
    }
}