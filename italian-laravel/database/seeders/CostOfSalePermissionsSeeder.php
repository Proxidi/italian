<?php

namespace Database\Seeders;

use App\Support\CorePermissions;

class CostOfSalePermissionsSeeder extends ModulePermissionsSeeder
{
    public const PERMISSIONS = CorePermissions::GROUPS['cost-of-sale'];

    protected string $group = 'cost-of-sale';
}
