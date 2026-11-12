<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;

class CurrencyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'sales', 'warehouse'], true);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Currency $category): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Currency $category): bool
    {
        return $this->create($user);
    }
}
