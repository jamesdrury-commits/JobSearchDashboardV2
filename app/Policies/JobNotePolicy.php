<?php

namespace App\Policies;

use App\Models\JobNote;
use App\Models\User;

class JobNotePolicy
{
    public function view(User $user, JobNote $jobNote): bool
    {
        return $jobNote->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, JobNote $jobNote): bool
    {
        return $this->view($user, $jobNote);
    }

    public function delete(User $user, JobNote $jobNote): bool
    {
        return $this->view($user, $jobNote);
    }
}
