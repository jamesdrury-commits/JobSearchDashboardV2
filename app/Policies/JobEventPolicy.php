<?php

namespace App\Policies;

use App\Models\JobEvent;
use App\Models\User;

class JobEventPolicy
{
    public function view(User $user, JobEvent $jobEvent): bool
    {
        return $jobEvent->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, JobEvent $jobEvent): bool
    {
        return $this->view($user, $jobEvent);
    }
}
