<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticates_an_editor_and_returns_their_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', UserRole::Editor->value);
        $this->assertAuthenticatedAs($user);
    }

    public function test_rejects_invalid_login_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
        $this->assertGuest();
    }

    public function test_logs_out_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('logout'));

        $response->assertNoContent();
        $this->assertGuest();
    }
}
