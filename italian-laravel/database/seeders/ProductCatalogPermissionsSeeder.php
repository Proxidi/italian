<?php

namespace Database\Seeders;

use App\Support\CorePermissions;

class ProductCatalogPermissionsSeeder extends ModulePermissionsSeeder
{
    public const PERMISSIONS = CorePermissions::GROUPS['catalog'];

    protected string $group = 'catalog';
}
