<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('admin:bootstrap')]
#[Description('Create the configured initial administrator when none exists')]
class BootstrapAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (User::query()->where('role', UserRole::Admin)->exists()) {
            $this->components->info('Ein Administrator existiert bereits.');

            return self::SUCCESS;
        }

        $name = config('services.initial_admin.name');
        $email = config('services.initial_admin.email');
        $password = config('services.initial_admin.password');

        if (! is_string($name) || ! is_string($email) || ! is_string($password) || $name === '' || $email === '' || $password === '') {
            $this->components->error('INITIAL_ADMIN_NAME, INITIAL_ADMIN_EMAIL und INITIAL_ADMIN_PASSWORD müssen gesetzt sein.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
        ]);

        $this->components->info('Der erste Administrator wurde angelegt. Entferne jetzt INITIAL_ADMIN_PASSWORD aus der Deployment-Umgebung.');

        return self::SUCCESS;
    }
}
