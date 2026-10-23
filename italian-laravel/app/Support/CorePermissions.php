<?php

namespace App\Support;

final class CorePermissions
{
    public const ROLES = [
        1 => ['name' => 'superadmin', 'label' => 'Dueño'],
        2 => ['name' => 'admin', 'label' => 'Manager'],
        3 => ['name' => 'root', 'label' => 'Gerente'],
        4 => ['name' => 'submanager', 'label' => 'SubManager'],
        5 => ['name' => 'socio', 'label' => 'Socio'],
    ];

    public const GROUPS = [
        'users-read' => ['users.directory.view', 'access-log.view'],
        'users-write' => ['users.create', 'users.update'],
        'configuration' => ['configuration.view', 'configuration.upload-window.view', 'configuration.upload-window.update', 'configuration.notifications.view', 'configuration.notifications.update'],
        'products' => ['products.view', 'products.units.view', 'products.units.create', 'products.units.update'],
        'ingredients' => ['products.ingredients.view', 'products.ingredients.conversions.update'],
        'catalog' => ['products.catalog.view', 'products.catalog.create', 'products.formulas.view', 'products.formulas.create', 'products.formulas.update'],
        'cost-of-sale' => ['products.cost-of-sale.view', 'products.cost-of-sale.update'],
    ];

    public static function names(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }

    public static function labels(): array
    {
        return array_column(self::ROLES, 'label', 'name');
    }

    public static function roleNamesFor(string $permission): array
    {
        return $permission === 'access-log.view' ? ['root', 'superadmin'] : ['root'];
    }
}
