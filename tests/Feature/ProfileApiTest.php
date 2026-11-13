<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_view_and_update_their_profile(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor, 'locale' => 'de']);
        Sanctum::actingAs($user);

        $this->getJson(route('v1.admin.profile.show'))
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.locale', 'de');

        $this->patchJson(route('v1.admin.profile.update'), [
            'name' => 'Profil Aktualisiert',
            'email' => $user->email,
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Profil Aktualisiert')
            ->assertJsonPath('data.locale', 'en');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'locale' => 'en']);
    }

    public function test_user_must_provide_the_current_password_to_change_it(): void
    {
        $user = User::factory()->create(['password' => 'current-password', 'locale' => 'de']);
        Sanctum::actingAs($user);

        $this->patchJson(route('v1.admin.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'de',
            'password' => 'a-new-secure-password',
            'password_confirmation' => 'a-new-secure-password',
            'current_password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }
}
