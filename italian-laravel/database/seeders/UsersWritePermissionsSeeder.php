<?php

namespace Database\Seeders;

use App\Support\CorePermissions;

class UsersWritePermissionsSeeder extends ModulePermissionsSeeder
{
    public const PERMISSIONS = CorePermissions::GROUPS['users-write'];

    protected string $group = 'users-write';
}
