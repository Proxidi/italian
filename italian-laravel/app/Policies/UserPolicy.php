<?php

namespace App\Policies;

use App\Models\Usuario;

class UserPolicy
{
    public function create(Usuario $actor): bool
    {
        return $actor->can('users.directory.view') && $actor->can('users.create');
    }

    public function update(Usuario $actor, Usuario $subject): bool
    {
        // Legacy administrative editing has no target-role/plaza exclusion.
        return $actor->can('users.directory.view') && $actor->can('users.update');
    }
}
