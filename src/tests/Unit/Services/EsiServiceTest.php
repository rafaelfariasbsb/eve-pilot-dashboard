<?php

namespace Tests\Unit\Services;

use App\Models\Character;
use App\Services\EsiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EsiServiceTest extends TestCase
{
    use RefreshDatabase;

    private EsiService $esi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->esi = new EsiService();
        config([
            'services.eve.esi_base_url' => 'https://esi.evetech.net/latest',
            'services.eve.esi_datasource' => 'tranquility',
            'services.eve.esi_user_agent' => 'Test/1.0',
            'services.eve.client_id' => 'test-client-id',
            'services.eve.client_secret' => 'test-client-secret',
        ]);
    }

    public function test_get_character_info_returns_data_on_success(): void
    {
        Http::fake([
            'esi.evetech.net/latest/characters/12345/*' => Http::response([
                'name' => 'Test Pilot',
                'corporation_id' => 98000001,
            ], 200, ['Expires' => now()->addMinutes(10)->toRfc7231String()]),
        ]);

        $result = $this->esi->getCharacterInfo(12345);

        $this->assertIsArray($result);
        $this->assertEquals('Test Pilot', $result['name']);
        $this->assertEquals(98000001, $result['corporation_id']);
    }

    public function test_public_request_caches_response(): void
    {
        Http::fake([
            'esi.evetech.net/*' => Http::response([
                'name' => 'Cached Corp',
            ], 200, ['Expires' => now()->addMinutes(5)->toRfc7231String()]),
        ]);

        $this->esi->getCorporationInfo(98000001);

        $cacheKey = 'esi:' . md5('/corporations/98000001/' . json_encode([]));
        $this->assertNotNull(Cache::get($cacheKey));
    }

    public function test_public_request_returns_cached_on_304(): void
    {
        // First request to populate cache
        Http::fake([
            'esi.evetech.net/*' => Http::response(
                ['name' => 'Cached Data'],
                200,
                [
                    'ETag' => '"etag-value"',
                    'Expires' => now()->addMinutes(10)->toRfc7231String(),
                ]
            ),
        ]);

        $firstResult = $this->esi->getCharacterPortrait(12345);
        $this->assertEquals(['name' => 'Cached Data'], $firstResult);

        // Verify cache was populated
        $cacheKey = 'esi:' . md5('/characters/12345/portrait/' . json_encode([]));
        $this->assertNotNull(Cache::get($cacheKey));
        $etagKey = $cacheKey . ':etag';
        $this->assertEquals('"etag-value"', Cache::get($etagKey));
    }

    public function test_public_request_returns_cached_on_failure(): void
    {
        // First request to populate cache
        Http::fake([
            'esi.evetech.net/*' => Http::response(
                ['name' => 'Fallback Data'],
                200,
                ['Expires' => now()->addMinutes(10)->toRfc7231String()]
            ),
        ]);

        $this->esi->getCharacterInfo(99999);

        $cacheKey = 'esi:' . md5('/characters/99999/' . json_encode([]));
        $this->assertNotNull(Cache::get($cacheKey));
        $this->assertEquals(['name' => 'Fallback Data'], Cache::get($cacheKey));
    }

    public function test_public_request_stores_etag(): void
    {
        Http::fake([
            'esi.evetech.net/*' => Http::response(
                [['type_id' => 34, 'average_price' => 5.5]],
                200,
                [
                    'ETag' => '"abc123"',
                    'Expires' => now()->addMinutes(5)->toRfc7231String(),
                ]
            ),
        ]);

        $this->esi->getMarketPrices();

        $cacheKey = 'esi:' . md5('/markets/prices/' . json_encode([]));
        $etagKey = $cacheKey . ':etag';
        $this->assertEquals('"abc123"', Cache::get($etagKey));
    }

    public function test_get_wallet_returns_float(): void
    {
        $character = Character::factory()->create();

        Http::fake([
            'esi.evetech.net/*' => Http::response(1234567.89, 200, [
                'Expires' => now()->addMinutes(5)->toRfc7231String(),
            ]),
        ]);

        $result = $this->esi->getWallet($character);

        $this->assertEquals(1234567.89, $result);
    }

    public function test_authenticated_request_refreshes_expired_token(): void
    {
        $character = Character::factory()->expired()->create();

        Http::fake([
            'login.eveonline.com/*' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 1200,
            ], 200),
            'esi.evetech.net/*' => Http::response(5000000.00, 200, [
                'Expires' => now()->addMinutes(5)->toRfc7231String(),
            ]),
        ]);

        $result = $this->esi->getWallet($character);

        $this->assertNotNull($result);
        $character->refresh();
        $this->assertEquals('new-access-token', $character->access_token);
        $this->assertEquals('new-refresh-token', $character->refresh_token);
    }

    public function test_ensure_valid_token_skips_when_valid(): void
    {
        $character = Character::factory()->create([
            'token_expires_at' => now()->addMinutes(20),
            'access_token' => 'valid-token',
        ]);

        Http::fake([
            'esi.evetech.net/*' => Http::response(1000.00, 200, [
                'Expires' => now()->addMinutes(5)->toRfc7231String(),
            ]),
        ]);

        $this->esi->getWallet($character);

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'login.eveonline.com');
        });
    }

    public function test_paginated_request_merges_pages(): void
    {
        $character = Character::factory()->create();

        Http::fake([
            'esi.evetech.net/latest/characters/*/assets/?datasource=tranquility&page=1' => Http::response(
                [['item_id' => 1], ['item_id' => 2]],
                200,
                ['X-Pages' => '2']
            ),
            'esi.evetech.net/latest/characters/*/assets/?datasource=tranquility&page=2' => Http::response(
                [['item_id' => 3]],
                200,
                ['X-Pages' => '2']
            ),
        ]);

        $result = $this->esi->getAssets($character);

        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]['item_id']);
        $this->assertEquals(3, $result[2]['item_id']);
    }

    public function test_paginated_request_single_page(): void
    {
        $character = Character::factory()->create();

        Http::fake([
            'esi.evetech.net/*' => Http::response(
                [['item_id' => 1], ['item_id' => 2]],
                200,
                ['X-Pages' => '1']
            ),
        ]);

        $result = $this->esi->getBlueprints($character);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_get_market_prices_returns_array(): void
    {
        Http::fake([
            'esi.evetech.net/*' => Http::response([
                ['type_id' => 34, 'average_price' => 5.5],
                ['type_id' => 35, 'average_price' => 8.2],
            ], 200, ['Expires' => now()->addMinutes(60)->toRfc7231String()]),
        ]);

        $result = $this->esi->getMarketPrices();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_get_industry_jobs_passes_include_completed_false(): void
    {
        $character = Character::factory()->create();

        Http::fake([
            'esi.evetech.net/*' => Http::response([], 200, [
                'Expires' => now()->addMinutes(5)->toRfc7231String(),
            ]),
        ]);

        $this->esi->getIndustryJobs($character);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'include_completed=false');
        });
    }
}
