<?php

namespace App\Faq\Enum;

use App\Shared\Helper\Translation;

enum FaqFlash
{
    case CATEGORY_CREATED;
    case CATEGORIES_REORDERED;
    case CATEGORY_UPDATED;
    case CATEGORY_SHOWN;
    case CATEGORY_HIDDEN;
    case CATEGORY_DELETED;
    case QUESTION_CREATED;
    case QUESTIONS_REORDERED;
    case QUESTION_UPDATED;
    case QUESTION_SHOWN;
    case QUESTION_HIDDEN;
    case QUESTION_DELETED;

    public static function categoryVisibility(bool $visible): self
    {
        if ($visible) {
            return self::CATEGORY_SHOWN;
        }

        return self::CATEGORY_HIDDEN;
    }

    public static function questionVisibility(bool $visible): self
    {
        if ($visible) {
            return self::QUESTION_SHOWN;
        }

        return self::QUESTION_HIDDEN;
    }

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::CATEGORY_CREATED => Translation::translate('Rubrique « %s » ajoutée.'),
            self::CATEGORIES_REORDERED => Translation::translate('Ordre des rubriques enregistré.'),
            self::CATEGORY_UPDATED => Translation::translate('Rubrique « %s » modifiée.'),
            self::CATEGORY_SHOWN => Translation::translate('Rubrique « %s » visible.'),
            self::CATEGORY_HIDDEN => Translation::translate('Rubrique « %s » masquée.'),
            self::CATEGORY_DELETED => Translation::translate('Rubrique « %s » supprimée avec ses questions.'),
            self::QUESTION_CREATED => Translation::translate('Question ajoutée à « %s ».'),
            self::QUESTIONS_REORDERED => Translation::translate('Ordre des questions enregistré.'),
            self::QUESTION_UPDATED => Translation::translate('Question modifiée.'),
            self::QUESTION_SHOWN => Translation::translate('Question visible.'),
            self::QUESTION_HIDDEN => Translation::translate('Question masquée.'),
            self::QUESTION_DELETED => Translation::translate('Question supprimée.'),
        };
    }
}