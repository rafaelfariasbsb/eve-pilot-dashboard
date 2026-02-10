<?php

namespace App\Services;

use App\Models\Character;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EsiService
{
    private string $baseUrl;
    private string $datasource;
    private string $userAgent;

    public function __construct()
    {
        $this->baseUrl = config('services.eve.esi_base_url', 'https://esi.evetech.net/latest');
        $this->datasource = config('services.eve.esi_datasource', 'tranquility');
        $this->userAgent = config('services.eve.esi_user_agent', 'EVE Pilot Dashboard/1.0');
    }

    public function getCharacterInfo(int $characterId): ?array
    {
        return $this->publicRequest("/characters/{$characterId}/");
    }

    public function getCorporationInfo(int $corporationId): ?array
    {
        return $this->publicRequest("/corporations/{$corporationId}/");
    }

    public function getCharacterPortrait(int $characterId): ?array
    {
        return $this->publicRequest("/characters/{$characterId}/portrait/");
    }

    public function getWallet(Character $character): ?float
    {
        $data = $this->authenticatedRequest($character, "/characters/{$character->character_id}/wallet/");
        return $data;
    }

    public function getAssets(Character $character): ?array
    {
        return $this->paginatedRequest($character, "/characters/{$character->character_id}/assets/");
    }

    public function getBlueprints(Character $character): ?array
    {
        return $this->paginatedRequest($character, "/characters/{$character->character_id}/blueprints/");
    }

    public function getIndustryJobs(Character $character): ?array
    {
        return $this->authenticatedRequest($character, "/characters/{$character->character_id}/industry/jobs/", [
            'include_completed' => 'false',
        ]);
    }

    public function getMarketPrices(): ?array
    {
        return $this->publicRequest('/markets/prices/');
    }

    public function getIndustrySystems(): ?array
    {
        return $this->publicRequest('/industry/systems/');
    }

    private function publicRequest(string $endpoint, array $query = []): mixed
    {
        $cacheKey = 'esi:' . md5($endpoint . json_encode($query));
        $etagKey = $cacheKey . ':etag';

        $cachedData = Cache::get($cacheKey);
        $cachedEtag = Cache::get($etagKey);

        $headers = ['User-Agent' => $this->userAgent];
        if ($cachedEtag) {
            $headers['If-None-Match'] = $cachedEtag;
        }

        $response = Http::withHeaders($headers)
            ->retry(3, 200, function (\Throwable $e) {
                return !in_array($e->getCode(), [420, 429]);
            })
            ->get($this->baseUrl . $endpoint, array_merge($query, [
                'datasource' => $this->datasource,
            ]));

        if ($response->status() === 304 && $cachedData !== null) {
            return $cachedData;
        }

        if ($response->successful()) {
            $ttl = $this->parseCacheTtl($response);
            $data = $response->json();

            Cache::put($cacheKey, $data, $ttl);
            if ($etag = $response->header('ETag')) {
                Cache::put($etagKey, $etag, $ttl + 60);
            }

            return $data;
        }

        Log::warning('ESI request failed', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $cachedData;
    }

    private function authenticatedRequest(Character $character, string $endpoint, array $query = []): mixed
    {
        $this->ensureValidToken($character);

        $cacheKey = 'esi:auth:' . $character->character_id . ':' . md5($endpoint . json_encode($query));
        $etagKey = $cacheKey . ':etag';

        $cachedData = Cache::get($cacheKey);
        $cachedEtag = Cache::get($etagKey);

        $headers = ['User-Agent' => $this->userAgent];
        if ($cachedEtag) {
            $headers['If-None-Match'] = $cachedEtag;
        }

        $response = Http::withToken($character->access_token)
            ->withHeaders($headers)
            ->retry(3, 200, function (\Throwable $e) {
                return !in_array($e->getCode(), [420, 429]);
            })
            ->get($this->baseUrl . $endpoint, array_merge($query, [
                'datasource' => $this->datasource,
            ]));

        if ($response->status() === 304 && $cachedData !== null) {
            return $cachedData;
        }

        if ($response->successful()) {
            $ttl = $this->parseCacheTtl($response);
            $data = $response->json();

            Cache::put($cacheKey, $data, $ttl);
            if ($etag = $response->header('ETag')) {
                Cache::put($etagKey, $etag, $ttl + 60);
            }

            return $data;
        }

        Log::warning('ESI authenticated request failed', [
            'endpoint' => $endpoint,
            'character' => $character->character_id,
            'status' => $response->status(),
        ]);

        return $cachedData;
    }

    private function paginatedRequest(Character $character, string $endpoint, array $query = []): ?array
    {
        $this->ensureValidToken($character);

        $allData = [];
        $page = 1;

        do {
            $response = Http::withToken($character->access_token)
                ->withHeaders(['User-Agent' => $this->userAgent])
                ->retry(3, 200)
                ->get($this->baseUrl . $endpoint, array_merge($query, [
                    'datasource' => $this->datasource,
                    'page' => $page,
                ]));

            if (!$response->successful()) {
                Log::warning('ESI paginated request failed', [
                    'endpoint' => $endpoint,
                    'page' => $page,
                    'status' => $response->status(),
                ]);
                break;
            }

            $data = $response->json();
            if (is_array($data)) {
                $allData = array_merge($allData, $data);
            }

            $totalPages = (int) ($response->header('X-Pages') ?? 1);
            $page++;
        } while ($page <= $totalPages);

        return $allData;
    }

    private function ensureValidToken(Character $character): void
    {
        if (!$character->isTokenExpired()) {
            return;
        }

        $response = Http::asForm()
            ->withBasicAuth(
                config('services.eve.client_id'),
                config('services.eve.client_secret')
            )
            ->post('https://login.eveonline.com/v2/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $character->refresh_token,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $character->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'token_expires_at' => now()->addSeconds($data['expires_in'] - 30),
            ]);
            $character->refresh();
        } else {
            Log::error('Token refresh failed', [
                'character' => $character->character_id,
                'status' => $response->status(),
            ]);
        }
    }

    private function parseCacheTtl(Response $response): int
    {
        $expires = $response->header('Expires');
        if ($expires) {
            $ttl = Carbon::parse($expires)->diffInSeconds(now(), false);
            return max(abs($ttl), 60);
        }
        return 300;
    }
}
