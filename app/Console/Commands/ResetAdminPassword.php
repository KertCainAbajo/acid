<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetAdminPassword extends Command
{
    /**
     * @var string
     */
    protected $signature = 'admin:reset-password
                            {email? : The administrator\'s email address (defaults to the only admin)}
                            {--password= : The new password (a strong random one is generated if omitted)}
                            {--reset-two-factor : Also turn off two-factor so the admin can set it up again (for a lost phone)}';

    /**
     * @var string
     */
    protected $description = 'Reset an administrator\'s password and sign them out everywhere';

    public function handle(): int
    {
        $admins = User::where('role', User::ROLE_ADMIN)
            ->when($this->argument('email'), fn ($query, string $email) => $query->where('email', $email))
            ->get();

        if ($admins->count() !== 1) {
            $this->error($admins->isEmpty()
                ? 'No matching administrator was found.'
                : 'There are several administrators. Pass the email address: '.$admins->pluck('email')->implode(', '));

            return self::FAILURE;
        }

        $password = $this->option('password') ?: Str::password(16, symbols: false);
        if (mb_strlen($password) < 10) {
            $this->error('The password must be at least 10 characters.');

            return self::FAILURE;
        }

        $admin = $admins->first();
        $admin->forceFill(['password' => $password, 'password_changed_at' => now(), 'remember_token' => Str::random(60), 'is_active' => true, 'locked_until' => null, 'failed_login_attempts' => 0])->save();
        if ($this->option('reset-two-factor')) {
            $admin->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
            $this->warn('Two-factor was turned off. The admin must set it up again right after signing in.');
        }
        $admin->tokens()->delete();

        $this->info("Password reset for {$admin->email}.");
        if (! $this->option('password')) {
            $this->line("New password: <comment>{$password}</comment>");
            $this->line('Sign in and change it from Account settings.');
        }

        return self::SUCCESS;
    }
}
