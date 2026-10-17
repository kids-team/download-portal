<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_routes_return_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/me');

        $response->assertUnauthorized();
    }

    #[DataProvider('adminRoles')]
    public function test_admin_and_editor_can_retrieve_their_admin_profile(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', $role->value);
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function adminRoles(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'editor' => [UserRole::Editor],
        ];
    }
}
