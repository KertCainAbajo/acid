<?php

namespace App\Policies;

use App\Models\FoodLog;
use App\Models\User;

class FoodLogPolicy
{
    /**
     * Patients may only correct their own food entry.
     */
    public function update(User $user, FoodLog $foodLog): bool
    {
        return $user->is($foodLog->user);
    }

    /**
     * Patients may only delete their own food entry.
     */
    public function delete(User $user, FoodLog $foodLog): bool
    {
        return $user->is($foodLog->user);
    }
}
