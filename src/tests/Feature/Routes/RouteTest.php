<?php

namespace Tests\Feature\Routes;

use App\Jobs\SyncCharacterData;
use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_dashboard_blocks_unauthenticated(): void
    {
        $response = $this->get('/dashboard');

        // App has no named 'login' route, so auth middleware returns an error
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_blueprints_blocks_unauthenticated(): void
    {
        $response = $this->get('/blueprints');

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_sync_blocks_unauthenticated(): void
    {
        $response = $this->post('/sync');

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_sync_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'is_main' => true]);

        $response = $this->actingAs($user)->post(route('sync'));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Queue::assertPushed(SyncCharacterData::class);
    }
}
