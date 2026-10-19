<?php

namespace App\Admin\Dashboard;

class DashboardStat
{
    public function __construct(
        private int|string $value,
        private string $note,
        private bool $alert = false,
    ) {
    }

    public function getValue(): int|string
    {
        return $this->value;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function isAlert(): bool
    {
        return $this->alert;
    }
}