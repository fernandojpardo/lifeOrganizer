<?php

namespace App\Policies;

use App\Models\SavingGoal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SavingGoalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function view(User $user, SavingGoal $savingGoal): bool
    {
        return $user->id === $savingGoal->user_id;
    }

    public function update(User $user, SavingGoal $savingGoal): bool
    {
        return $user->id === $savingGoal->user_id;
    }

    public function delete(User $user, SavingGoal $savingGoal): bool
    {
        return $user->id === $savingGoal->user_id;
    }
}
