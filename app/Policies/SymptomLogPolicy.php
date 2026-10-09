<?php

namespace App\Policies;

use App\Models\SymptomLog;
use App\Models\User;

class SymptomLogPolicy
{
    /**
     * Patients may only correct their own symptom entry.
     */
    public function update(User $user, SymptomLog $symptomLog): bool
    {
        return $user->is($symptomLog->user);
    }

    /**
     * Patients may only delete their own symptom entry.
     */
    public function delete(User $user, SymptomLog $symptomLog): bool
    {
        return $user->is($symptomLog->user);
    }
}
