<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

class LegacyUserRoleSynchronizer
{
    public const ROLES = [
        1 => 'superadmin',
        2 => 'admin',
        3 => 'root',
        4 => 'submanager',
        5 => 'socio',
    ];

    public function nameFor(int $legacyRoleId): ?string
    {
        return self::ROLES[$legacyRoleId] ?? null;
    }

    /**
     * Reconciles the Spatie assignment with the legacy source of truth.
     *
     * No role is created here: an absent web role is a configuration error and
     * must not turn a successful legacy password check into implicit access.
     */
    public function synchronize(User $user): bool
    {
        $name = $this->nameFor((int) $user->rol_id);

        if ($name === null) {
            return false;
        }

        $role = Role::query()
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->first();

        if ($role === null) {
            return false;
        }

        $assigned = $user->roles()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        if ($assigned !== [$name]) {
            $user->syncRoles([$role]);
        }

        return true;
    }
}
