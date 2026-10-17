<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('users.directory.view') && $actor->can('users.create');
    }

    public function update(User $actor, User $subject): bool
    {
        // Legacy administrative editing has no target-role/plaza exclusion.
        return $actor->can('users.directory.view') && $actor->can('users.update');
    }
}
