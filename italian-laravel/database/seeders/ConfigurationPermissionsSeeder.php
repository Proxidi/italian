<?php

namespace Database\Seeders;

use App\Support\CorePermissions;

class ConfigurationPermissionsSeeder extends ModulePermissionsSeeder
{
    public const PERMISSIONS = CorePermissions::GROUPS['configuration'];

    protected string $group = 'configuration';
}
