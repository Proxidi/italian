<?php

namespace Database\Seeders;

use App\Support\CorePermissions;

class IngredientsPermissionsSeeder extends ModulePermissionsSeeder
{
    public const PERMISSIONS = CorePermissions::GROUPS['ingredients'];

    protected string $group = 'ingredients';
}
