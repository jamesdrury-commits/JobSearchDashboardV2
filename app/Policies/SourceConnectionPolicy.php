<?php

namespace App\Policies;

use App\Models\SourceConnection;
use App\Models\User;

class SourceConnectionPolicy
{
    public function view(User $user, SourceConnection $sourceConnection): bool
    {
        return $sourceConnection->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SourceConnection $sourceConnection): bool
    {
        return $this->view($user, $sourceConnection);
    }

    public function delete(User $user, SourceConnection $sourceConnection): bool
    {
        return $this->view($user, $sourceConnection);
    }
}
