<?php

namespace Database\Seeders;

use App\Support\CorePermissions;

class UsersReadPermissionsSeeder extends ModulePermissionsSeeder
{
    public const PERMISSIONS = CorePermissions::GROUPS['users-read'];

    protected string $group = 'users-read';
}
