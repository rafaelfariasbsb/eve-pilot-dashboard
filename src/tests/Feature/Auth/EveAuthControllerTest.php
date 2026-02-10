<?php

namespace Tests\Feature\Auth;

use App\Models\Character;
use App\Models\User;
use App\Services\EsiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class EveAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_redirects_to_eve_sso(): void
    {
        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect('https://login.eveonline.com/v2/oauth/authorize'));

        Socialite::shouldReceive('driver')
            ->with('eveonline')
            ->andReturn($provider);

        $response = $this->get(route('eve.login'));

        $response->assertRedirect();
        $response->assertRedirectContains('login.eveonline.com');
    }

    public function test_callback_creates_user_and_character(): void
    {
        $ssoUser = Mockery::mock('Laravel\Socialite\Contracts\User');
        $ssoUser->character_id = '95000001';
        $ssoUser->character_name = 'Test Pilot';
        $ssoUser->token = 'test-access-token';
        $ssoUser->refreshToken = 'test-refresh-token';
        $ssoUser->expiresIn = 1200;
        $ssoUser->accessTokenResponseBody = ['scope' => 'esi-wallet.read_character_wallet.v1 esi-assets.read_assets.v1'];

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($ssoUser);

        Socialite::shouldReceive('driver')
            ->with('eveonline')
            ->andReturn($provider);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getCharacterInfo')->with(95000001)->andReturn([
            'corporation_id' => 98000001,
            'alliance_id' => null,
        ]);
        $esiMock->shouldReceive('getCorporationInfo')->with(98000001)->andReturn([
            'name' => 'Test Corp',
        ]);
        $esiMock->shouldReceive('getCharacterPortrait')->with(95000001)->andReturn([
            'px128x128' => 'https://images.evetech.net/characters/95000001/portrait?size=128',
        ]);

        $this->app->instance(EsiService::class, $esiMock);

        $response = $this->get(route('eve.callback'));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['name' => 'Test Pilot']);
        $this->assertDatabaseHas('characters', [
            'character_id' => 95000001,
            'name' => 'Test Pilot',
            'corporation_id' => 98000001,
            'corporation_name' => 'Test Corp',
            'is_main' => true,
        ]);
    }

    public function test_callback_links_character_to_existing_user(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id, 'is_main' => true]);

        $ssoUser = Mockery::mock('Laravel\Socialite\Contracts\User');
        $ssoUser->character_id = '95000002';
        $ssoUser->character_name = 'Alt Character';
        $ssoUser->token = 'alt-token';
        $ssoUser->refreshToken = 'alt-refresh';
        $ssoUser->expiresIn = 1200;
        $ssoUser->accessTokenResponseBody = ['scope' => 'esi-wallet.read_character_wallet.v1'];

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($ssoUser);

        Socialite::shouldReceive('driver')
            ->with('eveonline')
            ->andReturn($provider);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getCharacterInfo')->andReturn(['corporation_id' => 98000001, 'alliance_id' => null]);
        $esiMock->shouldReceive('getCorporationInfo')->andReturn(['name' => 'Test Corp']);
        $esiMock->shouldReceive('getCharacterPortrait')->andReturn(['px128x128' => 'https://example.com/portrait.jpg']);
        $this->app->instance(EsiService::class, $esiMock);

        $response = $this->actingAs($user)->get(route('eve.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('characters', [
            'character_id' => 95000002,
            'user_id' => $user->id,
            'is_main' => false,
        ]);
    }

    public function test_callback_updates_existing_character(): void
    {
        $user = User::factory()->create();
        Character::factory()->create([
            'user_id' => $user->id,
            'character_id' => 95000001,
            'name' => 'Old Name',
            'access_token' => 'old-token',
        ]);

        $ssoUser = Mockery::mock('Laravel\Socialite\Contracts\User');
        $ssoUser->character_id = '95000001';
        $ssoUser->character_name = 'New Name';
        $ssoUser->token = 'new-token';
        $ssoUser->refreshToken = 'new-refresh';
        $ssoUser->expiresIn = 1200;
        $ssoUser->accessTokenResponseBody = ['scope' => 'esi-wallet.read_character_wallet.v1'];

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($ssoUser);

        Socialite::shouldReceive('driver')
            ->with('eveonline')
            ->andReturn($provider);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getCharacterInfo')->andReturn(['corporation_id' => 98000001, 'alliance_id' => null]);
        $esiMock->shouldReceive('getCorporationInfo')->andReturn(['name' => 'Corp']);
        $esiMock->shouldReceive('getCharacterPortrait')->andReturn(['px64x64' => 'https://example.com/p.jpg']);
        $this->app->instance(EsiService::class, $esiMock);

        $this->get(route('eve.callback'));

        $this->assertDatabaseHas('characters', [
            'character_id' => 95000001,
            'name' => 'New Name',
            'access_token' => 'new-token',
        ]);
        $this->assertDatabaseCount('characters', 1);
    }

    public function test_callback_sets_first_character_as_main(): void
    {
        $ssoUser = Mockery::mock('Laravel\Socialite\Contracts\User');
        $ssoUser->character_id = '95000001';
        $ssoUser->character_name = 'First Char';
        $ssoUser->token = 'token';
        $ssoUser->refreshToken = 'refresh';
        $ssoUser->expiresIn = 1200;
        $ssoUser->accessTokenResponseBody = ['scope' => ''];

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($ssoUser);

        Socialite::shouldReceive('driver')
            ->with('eveonline')
            ->andReturn($provider);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getCharacterInfo')->andReturn(['corporation_id' => null, 'alliance_id' => null]);
        $esiMock->shouldReceive('getCharacterPortrait')->andReturn([]);
        $this->app->instance(EsiService::class, $esiMock);

        $this->get(route('eve.callback'));

        $this->assertDatabaseHas('characters', [
            'character_id' => 95000001,
            'is_main' => true,
        ]);
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
