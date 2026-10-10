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
                            {--password= : The new password (a strong random one is generated if omitted)}';

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
        $admin->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'is_active' => true])->save();
        $admin->tokens()->delete();

        $this->info("Password reset for {$admin->email}.");
        if (! $this->option('password')) {
            $this->line("New password: <comment>{$password}</comment>");
            $this->line('Sign in and change it from Account settings.');
        }

        return self::SUCCESS;
    }
}
