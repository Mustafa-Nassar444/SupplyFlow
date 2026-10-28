<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'sales', 'warehouse'], true);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Category $category): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->create($user);
    }
}
