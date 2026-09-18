<?php

namespace App\Policies;

use App\Models\News;
use App\Models\User;

class NewsPolicy
{
    public function update(User $user, News $news): bool
    {
        return $user->status === 'active' && ($user->role === 'super_admin' || $user->role === 'editor' || $news->author_id === $user->id);
    }

    public function delete(User $user, News $news): bool
    {
        return $user->status === 'active' && ($user->role === 'super_admin' || $news->author_id === $user->id);
    }

    public function publish(User $user): bool
    {
        return $user->status === 'active' && in_array($user->role, ['super_admin', 'editor'], true);
    }
}
