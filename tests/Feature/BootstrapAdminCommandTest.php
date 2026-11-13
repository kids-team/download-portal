<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BootstrapAdminCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_the_configured_initial_administrator(): void
    {
        config()->set('services.initial_admin', [
            'name' => 'Produktionsadmin',
            'email' => 'admin@example.com',
            'password' => 'ein-sicheres-passwort',
        ]);

        $this->artisan('admin:bootstrap')
            ->expectsOutputToContain('erste Administrator wurde angelegt')
            ->assertSuccessful();

        $admin = User::query()->sole();

        $this->assertSame('Produktionsadmin', $admin->name);
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(password_verify('ein-sicheres-passwort', $admin->password));
    }

    public function test_refuses_to_create_a_second_administrator(): void
    {
        User::factory()->create(['role' => UserRole::Admin]);

        $this->artisan('admin:bootstrap')
            ->expectsOutputToContain('Administrator existiert bereits')
            ->assertSuccessful();
    }
}
