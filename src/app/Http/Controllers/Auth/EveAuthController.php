<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\User;
use App\Services\EsiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class EveAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('eveonline')
            ->scopes([
                'esi-wallet.read_character_wallet.v1',
                'esi-assets.read_assets.v1',
                'esi-characters.read_blueprints.v1',
                'esi-industry.read_character_jobs.v1',
                'esi-skills.read_skills.v1',
            ])
            ->redirect();
    }

    public function callback(EsiService $esi): RedirectResponse
    {
        $ssoUser = Socialite::driver('eveonline')->user();

        $characterId = (int) $ssoUser->getId();
        $characterName = $ssoUser->getName();

        $charInfo = $esi->getCharacterInfo($characterId);
        $portrait = $esi->getCharacterPortrait($characterId);

        $corporationId = $charInfo['corporation_id'] ?? null;
        $corporationName = null;
        if ($corporationId) {
            $corpInfo = $esi->getCorporationInfo($corporationId);
            $corporationName = $corpInfo['name'] ?? null;
        }

        $allianceId = $charInfo['alliance_id'] ?? null;

        $character = Character::where('character_id', $characterId)->first();

        if ($character) {
            $user = $character->user;
        } else {
            if (Auth::check()) {
                $user = Auth::user();
            } else {
                $user = User::create([
                    'name' => $characterName,
                    'email' => $characterId . '@eve.local',
                    'password' => bcrypt(str()->random(32)),
                ]);
            }
        }

        $portraitUrl = $portrait['px128x128'] ?? $portrait['px64x64'] ?? null;

        $isMain = !$user->characters()->exists();

        Character::updateOrCreate(
            ['character_id' => $characterId],
            [
                'user_id' => $user->id,
                'name' => $characterName,
                'corporation_id' => $corporationId,
                'corporation_name' => $corporationName,
                'alliance_id' => $allianceId,
                'portrait_url' => $portraitUrl,
                'access_token' => $ssoUser->token,
                'refresh_token' => $ssoUser->refreshToken,
                'token_expires_at' => now()->addSeconds($ssoUser->expiresIn ?? 1199),
                'scopes' => explode(' ', $ssoUser->accessTokenResponseBody['scope'] ?? ''),
                'is_main' => $isMain,
            ]
        );

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }
}
