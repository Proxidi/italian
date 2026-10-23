<?php

namespace Database\Seeders;

use App\Support\CorePermissions;

class ProductsPermissionsSeeder extends ModulePermissionsSeeder
{
    public const PERMISSIONS = CorePermissions::GROUPS['products'];

    protected string $group = 'products';
}
