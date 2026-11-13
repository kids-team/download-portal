<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminTaxonomyApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('taxonomyIndexRoutes')]
    public function test_admin_can_list_taxonomies(string $route): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->getJson(route($route))
            ->assertOk()
            ->assertJsonPath('data.data', []);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function taxonomyIndexRoutes(): array
    {
        return [
            'categories' => ['v1.admin.categories.index'],
            'audiences' => ['v1.admin.audiences.index'],
            'tags' => ['v1.admin.tags.index'],
        ];
    }
}
