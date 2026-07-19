<?php

namespace App\Policies;

use App\Models\JobOperation;
use App\Models\User;

class JobOperationPolicy
{
    public function view(User $user, JobOperation $operation): bool
    {
        return $operation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
