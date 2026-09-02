<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\RegistrationSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SocialAuthController extends Controller
{
    public function redirect(Request $request, string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return response()->json(['success' => false, 'message' => 'Invalid provider.'], 400);
        }
        $redirectPath = $request->query('redirect');
        $driver = Socialite::driver($provider)->stateless();
        if (!empty($redirectPath) && is_string($redirectPath)) {
            $driver->with(['state' => $redirectPath]);
        }
        $url = $driver->redirect()->getTargetUrl();
        return response()->json(['success' => true, 'url' => $url]);
    }

    public function callbackWeb(Request $request, string $provider): RedirectResponse
    {
        $result = $this->handleCallback($provider);
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');
        if (isset($result['error'])) {
            return redirect("{$frontendUrl}/login?error=" . urlencode($result['error']));
        }
        $to = "{$frontendUrl}/auth/callback?token=" . urlencode($result['token']);
        $state = $request->query('state');
        if (!empty($state) && is_string($state) && str_starts_with($state, '/') && !str_contains($state, '//')) {
            $to .= '&redirect=' . urlencode($state);
        }
        return redirect($to);
    }

    public function callback(Request $request, string $provider): JsonResponse
    {
        $result = $this->handleCallback($provider);
        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 400);
        }
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $result['user'],
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ]);
    }

    /// Mobile: exchange a Google/Facebook OAuth access token (from google_sign_in) for an app token.
    public function token(Request $request, string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return response()->json(['success' => false, 'message' => 'Invalid provider.'], 400);
        }
        $accessToken = $request->input('access_token');
        if (empty($accessToken) || !is_string($accessToken)) {
            return response()->json(['success' => false, 'message' => 'Missing access token.'], 422);
        }
        try {
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($accessToken);
        } catch (\Exception $e) {
            Log::warning('Social mobile token exchange failed', [
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Google sign-in failed. Please try again.'], 400);
        }
        $result = $this->loginWithSocialUser($provider, $socialUser);
        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 400);
        }
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $result['user'],
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ]);
    }

    private function handleCallback(string $provider): array
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return ['error' => 'Invalid provider.'];
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            Log::warning('Social login token exchange failed', [
                'provider' => $provider,
                'redirect' => config('services.' . $provider . '.redirect'),
                'message' => $e->getMessage(),
            ]);
            return ['error' => 'Social login failed. Please try again.'];
        }

        return $this->loginWithSocialUser($provider, $socialUser);
    }

    private function loginWithSocialUser(string $provider, $socialUser): array
    {
        $email = $socialUser->getEmail();
        if (empty($email)) {
            return ['error' => 'Google did not share your email. Allow email access and try again.'];
        }

        try {
            $socialAccount = SocialAccount::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($socialAccount) {
                $user = $socialAccount->user;
                if (!$user) {
                    return ['error' => 'Linked account not found.'];
                }
                $socialAccount->update([
                    'token' => $socialUser->token ?? null,
                    'refresh_token' => $socialUser->refreshToken ?? null,
                    'expires_at' => isset($socialUser->expiresIn) ? now()->addSeconds($socialUser->expiresIn) : null,
                ]);
            } else {
                $user = User::where('email', $email)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? explode('@', $email)[0],
                        'email' => $email,
                        'password' => Hash::make(Str::random(32)),
                        'role' => 'customer',
                        'registration_source' => RegistrationSource::fromRequest(request()),
                        'email_verified_at' => now(),
                    ]);
                }

                SocialAccount::updateOrCreate(
                    ['provider' => $provider, 'provider_id' => $socialUser->getId()],
                    [
                        'user_id' => $user->id,
                        'token' => $socialUser->token ?? null,
                        'refresh_token' => $socialUser->refreshToken ?? null,
                        'expires_at' => isset($socialUser->expiresIn) ? now()->addSeconds($socialUser->expiresIn) : null,
                    ]
                );
            }

            // Google/Facebook email is trusted — always mark verified on social login.
            if (!$user->email_verified_at) {
                $user->update(['email_verified_at' => now()]);
            }

            if (!$user->isActive()) {
                return ['error' => 'Account is suspended or banned.'];
            }

            $user->update(['last_login_at' => now()]);
            $user->tokens()->delete();
            $token = $user->createToken('auth-token')->plainTextToken;

            return [
                'user' => $user->makeHidden(['password'])->load('addresses'),
                'token' => $token,
            ];
        } catch (\Exception $e) {
            Log::error('Social login account linking failed', [
                'provider' => $provider,
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
            return ['error' => 'Social login failed. Please try again.'];
        }
    }
}
