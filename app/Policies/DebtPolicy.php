<?php

namespace App\Policies;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DebtPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function view(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }

    public function update(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }

    public function delete(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }
}
