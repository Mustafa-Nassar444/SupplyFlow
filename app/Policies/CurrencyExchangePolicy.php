<?php

namespace App\Policies;

use App\Models\CurrencyExchange;
use App\Models\User;

class CurrencyExchangePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'sales', 'warehouse'], true);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, CurrencyExchange $category): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, CurrencyExchange $category): bool
    {
        return $this->create($user);
    }
}
