<?php

namespace App\Policies;

use App\Models\Language;
use App\Models\User;

class LanguagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Language $language): bool
    {
        return $this->viewAny($user);
    }
}
