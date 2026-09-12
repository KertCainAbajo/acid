<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_PATIENT = 'patient';

    public const ROLE_ADMIN = 'admin';

    public function foodLogs(): HasMany
    {
        return $this->hasMany(FoodLog::class);
    }

    public function symptomLogs(): HasMany
    {
        return $this->hasMany(SymptomLog::class);
    }

    public function sleepLogs(): HasMany
    {
        return $this->hasMany(SleepLog::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(Insight::class);
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class, 'actor_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPatient(): bool
    {
        return $this->role === self::ROLE_PATIENT;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
