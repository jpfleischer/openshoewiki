<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DiscordAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('discord')
            ->setScopes(['identify'])
            ->redirect();
    }

    public function callback()
    {
        $discordUser = Socialite::driver('discord')->user();

        if (! filled($discordUser->getId())) {
            throw new HttpException(422, 'Discord did not return a user ID.');
        }

        $user = DB::transaction(function () use ($discordUser): User {
            $discordId = (string) $discordUser->getId();
            $name = $discordUser->getName() ?: $discordUser->getNickname() ?: 'Discord User';
            $avatar = $discordUser->getAvatar();

            $user = User::query()
                ->where('discord_id', $discordId)
                ->first();

            if ($user !== null) {
                $user->forceFill([
                    'discord_id' => $discordId,
                    'discord_username' => $discordUser->getNickname() ?: $discordUser->getName(),
                    'discord_avatar' => $avatar,
                ]);

                $user->save();

                return $user;
            }

            $username = $this->generateUniqueUsername(
                $discordUser->getNickname() ?: $discordUser->getName() ?: 'discord-user',
                $discordId
            );

            return User::query()->create([
                'name' => $name,
                'username' => $username,
                'level' => User::REGULAR,
                'discord_id' => $discordId,
                'discord_username' => $discordUser->getNickname() ?: $discordUser->getName(),
                'discord_avatar' => $avatar,
            ]);
        });

        Auth::login($user, false);
        request()->session()->regenerate();

        return redirect()->intended('/profile');
    }

    protected function generateUniqueUsername(string $source, string $discordId): string
    {
        $normalized = Str::of($source)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9_-]+/', '-')
            ->trim('-_')
            ->value();

        if ($normalized === '' || ! preg_match('/^[a-z0-9]/', $normalized)) {
            $normalized = 'user-'.$discordId;
        }

        $base = Str::limit($normalized, 32, '');
        $candidate = $base;
        $suffix = 2;

        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = Str::limit($base, max(1, 40 - strlen((string) $suffix) - 1), '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
