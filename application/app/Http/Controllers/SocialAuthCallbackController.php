<?php

namespace App\Http\Controllers;

use App\Enums\AuthProviderType;
use App\Models\Project;
use App\Models\ProjectAccount;
use App\Models\ProjectAccountSession;
use App\Traits\GEOBlockTrait;
use App\Traits\IPHelperTrait;
use App\Traits\LogExceptionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthCallbackController extends Controller
{
    use LogExceptionTrait, IPHelperTrait, GEOBlockTrait;

    public function handle(string $authProvider, Request $request): void
    {
        try {

            // Validate requested auth provider
            $this->validateRequestedAuthProvider($authProvider);

            // Retrieve auth attempt from cookie
            [$authReference, $publicAPIKey] = $this->retrieveAuthAttemptFromCookie($request, $authProvider);

            // Load social user from callback
            $socialUser = $this->getSocialUser($authProvider);

            // Load project by public api key
            $project = $this->loadProjectByPublicAPIKey($publicAPIKey);

            // Check if this request should be geo-blocked based on project configuration
            $this->processGEOBlock($project, $request);

            // Upsert project account
            $projectAccount = $this->upsertProjectAccount($project, $authProvider, $socialUser);

            // Record project account session
            $this->recordProjectAccountSession($projectAccount, $authReference, $request);

            // Success
            exit(__('You have successfully logged in via :authProvider', ['authProvider' => $authProvider]));

        } catch (Throwable $exception) {

            // Handle exception
            $this->logException('Failed to handle social auth callback', $exception, [
                'authProvider' => $authProvider,
            ]);

            // Display generic error message
            exit(__('Failed to authenticate via :authProvider, please try again', ['authProvider' => $authProvider]));

        }
    }

    public function validateRequestedAuthProvider(string $authProvider): void
    {
        if (!in_array($authProvider, AuthProviderType::values(), true)) {
            exit(__(':currentProvider provider is not valid, supported providers are: :supportedProviders', [
                'currentProvider' => $authProvider,
                'supportedProviders' => implode(', ', AuthProviderType::values()),
            ]));
        }
    }

    public function retrieveAuthAttemptFromCookie(Request $request, string $authProvider): array
    {
        $authAttempt = $request->cookie($authProvider . '_auth_attempt');
        if (empty($authAttempt)) {
            exit(__('Login attempt session not found, please try again'));
        }

        [$authReference, $publicAPIKey] = explode('#:rewardengine:#', $authAttempt);
        if (empty($authReference) || strlen($authReference) > 512) {
            exit(__('Login attempt session is empty or larger than 512 characters, please try again'));
        }

        return [$authReference, $publicAPIKey];
    }

    public function getSocialUser(string $authProvider): User
    {
        $socialUser = null;

        try {
            $socialUser = Socialite::driver($authProvider)->user();
        } catch (Throwable) {}

        if (!$socialUser) {
            exit(__('Failed to authenticate via :authProvider, please try again', ['authProvider' => $authProvider]));
        }

        return $socialUser;
    }

    public function loadProjectByPublicAPIKey(string $publicAPIKey): Project
    {
        $project = Project::query()
            ->where('public_api_key', $publicAPIKey)
            ->select('id')
            ->first();

        if (!$project) {
            exit(__('Invalid login attempt session, please try again'));
        }

        return $project;
    }

    public function processGEOBlock(Project $project, Request $request): void
    {
        if ($this->isGEOBlocked($project, $request)) {
            exit(__('Access not permitted'));
        }
    }

    public function upsertProjectAccount(?Project $project, string $authProvider, ?User $socialUser): ProjectAccount
    {
        $projectAccount = ProjectAccount::query()
            ->where('project_id', $project->id)
            ->where('auth_provider', $authProvider)
            ->where('auth_provider_id', $socialUser->id)
            ->first();

        if (!$projectAccount) {
            $projectAccount = new ProjectAccount;
            $projectAccount->fill([
                'project_id' => $project->id,
                'auth_provider' => $authProvider,
                'auth_provider_id' => $socialUser->id,
            ]);
        }

        $projectAccount->auth_name = $socialUser->getName();
        $projectAccount->auth_email = $socialUser->getEmail();
        $projectAccount->auth_avatar = $socialUser->getAvatar();
        $projectAccount->save();

        return $projectAccount;
    }

    public function recordProjectAccountSession(ProjectAccount $projectAccount, string $authReference, Request $request): void
    {
        $projectAccountSession = new ProjectAccountSession;
        $projectAccountSession->fill([
            'project_account_id' => $projectAccount->id,
            'reference' => $authReference,
            'session_id' => Str::uuid(),
            'auth_country_code' => $this->getIPCountryCode($request),
            'authenticated_at' => now(),
        ]);
        $projectAccountSession->save();
    }
}
