<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardLayoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_read_or_update_the_layout(): void
    {
        $this->getJson(route('v1.admin.dashboard.layout.show'))->assertUnauthorized();
        $this->putJson(route('v1.admin.dashboard.layout.update'), ['widgets' => []])->assertUnauthorized();
    }

    public function test_user_can_save_and_load_a_personal_layout(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('v1.admin.dashboard.layout.show'))->assertOk()->assertJsonPath('data.widgets', null);
        $this->putJson(route('v1.admin.dashboard.layout.update'), ['widgets' => ['media', 'published', 'draft'], 'user_id' => $otherUser->id])
            ->assertOk()->assertJsonPath('data.widgets', ['media', 'published', 'draft']);
        $this->getJson(route('v1.admin.dashboard.layout.show'))->assertOk()->assertJsonPath('data.widgets', ['media', 'published', 'draft']);

        $this->assertSame(['media', 'published', 'draft'], $user->fresh()->dashboard_widget_order);
        $this->assertNull($otherUser->fresh()->dashboard_widget_order);
    }

    #[DataProvider('invalidLayouts')]
    public function test_rejects_invalid_layouts(array $widgets): void
    {
        $user = User::factory()->create();
        $user->dashboard_widget_order = ['draft', 'media'];
        $user->save();
        Sanctum::actingAs($user);

        $this->putJson(route('v1.admin.dashboard.layout.update'), ['widgets' => $widgets])->assertUnprocessable();

        $this->assertSame(['draft', 'media'], $user->fresh()->dashboard_widget_order);
    }

    public static function invalidLayouts(): array
    {
        return [
            'unknown widget' => [['draft', 'unknown']],
            'duplicate widget' => [['draft', 'draft']],
            'associative array' => [['first' => 'draft']],
            'too many widgets' => [['draft', 'media', 'published', 'draft']],
        ];
    }
}
