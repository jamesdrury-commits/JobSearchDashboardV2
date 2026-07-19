<?php

namespace App\Policies;

use App\Models\GeneratedDocument;
use App\Models\User;

class GeneratedDocumentPolicy
{
    public function view(User $user, GeneratedDocument $generatedDocument): bool
    {
        return $generatedDocument->user_id === $user->id;
    }

    public function download(User $user, GeneratedDocument $generatedDocument): bool
    {
        return $this->view($user, $generatedDocument);
    }

    public function delete(User $user, GeneratedDocument $generatedDocument): bool
    {
        return $this->view($user, $generatedDocument);
    }
}
