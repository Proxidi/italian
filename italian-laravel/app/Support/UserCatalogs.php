<?php

namespace App\Support;

use App\Models\Plaza;
use Spatie\Permission\Models\Role;

class UserCatalogs
{
    public static function forForm(): array
    {
        $labels = CorePermissions::labels();

        return [
            'plazas' => Plaza::query()->orderBy('id')->get(['id', 'nombre']),
            'roles' => Role::query()->where('guard_name', 'web')->whereIn('name', array_keys($labels))->orderBy('id')->get()
                ->filter(fn ($role) => $role->guard_name === 'web' && isset($labels[$role->name]))
                ->map(fn ($role) => (object) ['id' => $role->id, 'nombre' => $labels[$role->name]]),
        ];
    }
}
