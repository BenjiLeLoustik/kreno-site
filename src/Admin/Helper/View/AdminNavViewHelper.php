<?php

namespace App\Admin\Helper\View;

use App\Admin\Provider\AdminNavProvider;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class AdminNavViewHelper implements ViewFunctionInterface
{
    private const ITEMS = [
        ['key' => 'dashboard', 'label' => 'Vue d\'ensemble', 'route' => 'admin_dashboard', 'role' => 'ROLE_SUPPORT', 'icon' => 'layout-dashboard'],
        ['key' => 'moderation', 'label' => 'Modération des pros', 'route' => 'admin_moderation_index', 'role' => 'ROLE_MODERATOR', 'icon' => 'shield-check'],
        ['key' => 'reports', 'label' => 'Signalements', 'route' => 'admin_reports', 'role' => 'ROLE_MODERATOR', 'icon' => 'flag'],
        ['key' => 'contacts', 'label' => 'Messages reçus', 'route' => 'admin_contacts', 'role' => 'ROLE_SUPPORT', 'icon' => 'mail'],
        ['key' => 'users', 'label' => 'Utilisateurs', 'route' => 'admin_user_index', 'role' => 'ROLE_MODERATOR', 'icon' => 'users'],
        ['key' => 'establishments', 'label' => 'Établissements', 'route' => 'admin_establishments', 'role' => 'ROLE_MODERATOR', 'icon' => 'store'],
        ['key' => 'bookings', 'label' => 'Réservations', 'route' => 'admin_bookings', 'role' => 'ROLE_SUPPORT', 'icon' => 'calendar-check'],
        ['key' => 'billing', 'label' => 'Facturation', 'route' => 'admin_billing', 'role' => 'ROLE_ADMIN', 'icon' => 'receipt'],
        ['key' => 'catalog', 'label' => 'Catégories et villes', 'route' => 'admin_catalog_index', 'role' => 'ROLE_ADMIN', 'icon' => 'map-pin'],
        ['key' => 'pricing', 'label' => 'Tarifs et formules', 'route' => 'admin_pricing_index', 'role' => 'ROLE_ADMIN', 'icon' => 'tags'],
        ['key' => 'versions', 'label' => 'Versions du site', 'route' => 'admin_website_index', 'role' => 'ROLE_ADMIN', 'icon' => 'git-branch'],
        ['key' => 'errors', 'label' => 'Erreurs du site', 'route' => 'admin_app_logs', 'role' => 'ROLE_ADMIN', 'icon' => 'bug'],
        ['key' => 'settings', 'label' => 'Réglages et journal', 'route' => 'admin_settings_index', 'role' => 'ROLE_ADMIN', 'icon' => 'settings'],
    ];

    public function __construct(
        private AdminNavProvider $nav,
    ) {
    }

    public function getName(): string
    {
        return 'admin_nav';
    }

    public function __invoke(): array
    {
        $badges = $this->nav->badges();

        return array_map(static fn (array $item): array => $item + ['badge' => $badges[$item['key']] ?? 0], self::ITEMS);
    }
}