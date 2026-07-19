<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserPreference;

class UserPreferencePolicy
{
    public function view(User $user, UserPreference $userPreference): bool
    {
        return $userPreference->user_id === $user->id;
    }

    public function update(User $user, UserPreference $userPreference): bool
    {
        return $this->view($user, $userPreference);
    }
}
