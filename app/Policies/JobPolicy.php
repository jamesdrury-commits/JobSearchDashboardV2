<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function view(User $user, Job $job): bool
    {
        return $job->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Job $job): bool
    {
        return $this->view($user, $job);
    }

    public function delete(User $user, Job $job): bool
    {
        return $this->view($user, $job);
    }

    public function changeStatus(User $user, Job $job): bool
    {
        return $this->update($user, $job);
    }

    public function generateDocument(User $user, Job $job): bool
    {
        return $this->update($user, $job);
    }

    public function apply(User $user, Job $job): bool
    {
        return $this->update($user, $job);
    }
}
