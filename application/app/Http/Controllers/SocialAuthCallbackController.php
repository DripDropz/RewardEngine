<?php

namespace App\Http\Controllers;

use App\Enums\AuthProviderType;
use App\Models\ProjectAccount;
use App\Traits\GEOBlockTrait;
use App\Traits\IPHelperTrait;
use App\Traits\LogExceptionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthCallbackController extends Controller
{
    use LogExceptionTrait, IPHelperTrait, GEOBlockTrait;

    public function handle(string $authProvider, Request $request): void
    {
        try {

            // Validate requested auth provider
            if (!in_array($authProvider, AuthProviderType::values(), true)) {
                exit(__(':currentProvider provider is not valid, supported providers are: :supportedProviders', [
                    'currentProvider' => $authProvider,
                    'supportedProviders' => implode(', ', AuthProviderType::values()),
                ]));
            }

            // Retrieve project account
            $projectAccountId = Cache::get(sprintf('project-account:%s', md5($this->getIP($request))));
            if (empty($projectAccountId)) {
                exit(__('Login attempt session expired, please try again'));
            }
            $projectAccount = ProjectAccount::query()
                ->where('id', $projectAccountId)
                ->with('project')
                ->first();
            if (!$projectAccount) {
                exit(__('Login attempt session not found, please try again'));
            }

            // Check if this request should be geo-blocked
            if ($this->isGEOBlocked($projectAccount->project, $request)) {
                exit(__('Accept not permitted'));
            }

            // Load social user
            $socialUser = null;
            try {
                $socialUser = Socialite::driver($authProvider)->user();
            } catch (Throwable) {}
            if (!$socialUser) {
                exit(__('Failed to authenticate via :authProvider, please try again', ['authProvider' => $authProvider]));
            }

            // Update project account with social account info
            $projectAccount->update([
                'auth_provider_id' => $socialUser->id,
                'auth_name' => $socialUser->getName(),
                'auth_email' => $socialUser->getEmail(),
                'auth_avatar' => $socialUser->getAvatar(),
                'auth_country_code' => $this->getIPCountryCode($request),
                'authenticated_at' => now(),
            ]);

            // Success
            exit(__('You have successfully logged in via :authProvider', ['authProvider' => $authProvider]));

        } catch (Throwable $exception) {

            // Handle exception
            $this->logException('Failed to handle social auth callback', $exception);

            // Display generic error message
            exit(__('Failed to authenticate via :authProvider, please try again', ['authProvider' => $authProvider]));

        }
    }
}
