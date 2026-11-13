<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_and_update_an_editor_without_changing_their_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $created = $this->postJson(route('v1.admin.users.store'), [
            'name' => 'Redaktion Beispiel',
            'email' => 'redaktion@example.test',
            'role' => UserRole::Editor->value,
            'password' => 'ein-langes-passwort',
            'password_confirmation' => 'ein-langes-passwort',
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.email', 'redaktion@example.test')
            ->assertJsonPath('data.role', UserRole::Editor->value);

        $user = User::query()->where('email', 'redaktion@example.test')->firstOrFail();
        $passwordHash = $user->password;

        $this->patchJson(route('v1.admin.users.update', $user), [
            'name' => 'Redaktion Aktualisiert',
            'password' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Redaktion Aktualisiert');

        $this->assertSame($passwordHash, $user->refresh()->password);
    }

    public function test_editor_cannot_access_user_management(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->getJson(route('v1.admin.users.index'))->assertForbidden();
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $this->patchJson(route('v1.admin.users.update', $admin), ['role' => UserRole::Editor->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }
}
