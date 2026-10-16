<?php

namespace App\Admin\Dashboard;

use DateTimeImmutable;

interface BuilderStatInterface
{
    public function getKey(): string;

    public function getLabel(): string;

    public function getRoute(): string;

    public function getRole(): string;

    public function getPosition(): int;

    public function build(DateTimeImmutable $now): DashboardStat;
}