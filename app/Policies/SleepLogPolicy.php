<?php

namespace App\Policies;

use App\Models\SleepLog;
use App\Models\User;

class SleepLogPolicy
{
    /**
     * Patients may only correct their own sleep entry.
     */
    public function update(User $user, SleepLog $sleepLog): bool
    {
        return $user->is($sleepLog->user);
    }

    /**
     * Patients may only delete their own sleep entry.
     */
    public function delete(User $user, SleepLog $sleepLog): bool
    {
        return $user->is($sleepLog->user);
    }
}
