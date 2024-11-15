<?php

namespace App\Http\Controllers\API;

use App\Enums\AuthProviderType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAccount;
use App\Traits\GEOBlockTrait;
use App\Traits\IPHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    use IPHelperTrait, GEOBlockTrait;

    public function providers(): JsonResponse
    {
        // Return supported auth providers
        return response()
            ->json(AuthProviderType::values());
    }

    public function init(string $publicApiKey, string $authProvider, Request $request): RedirectResponse|JsonResponse
    {
        // Validate requested auth provider
        if (!in_array($authProvider, AuthProviderType::values(), true)) {
            return response()->json([
                'error' => __('Bad Request'),
                'reason' => __(':currentProvider provider is not valid, supported providers are: :supportedProviders', [
                    'currentProvider' => $authProvider,
                    'supportedProviders' => implode(', ', AuthProviderType::values()),
                ]),
            ], 400);
        }

        // Check if reference is provided in the request
        if (empty($request->get('reference'))) {
            return response()->json([
                'error' => __('Bad Request'),
                'reason' => __('The reference query string parameter is missing or empty'),
            ], 400);
        }

        // Load project by public api key
        $project = Project::query()
            ->where('public_api_key', $publicApiKey)
            ->first();

        // Check if project exists
        if (!$project) {
            return response()->json([
                'error' => __('Unauthorized'),
                'reason' => __('Invalid project public api key'),
            ], 401);
        }

        // Check if this request should be geo-blocked
        if ($this->isGEOBlocked($project, $request)) {
            return response()->json([
                'error' => __('Unauthorized'),
                'reason' => __('Accept not permitted'),
            ], 401);
        }

        // Setup project account
        $projectAccount = ProjectAccount::query()
            ->where('project_id', $project->id)
            ->where('reference', $request->get('reference'))
            ->where('auth_provider', $authProvider)
            ->first();
        if (!$projectAccount) {
            $projectAccount = new ProjectAccount;
            $projectAccount->fill([
                'project_id' => $project->id,
                'reference' => $request->get('reference'),
                'auth_provider' => $authProvider,
            ]);
            $projectAccount->save();
        }

        // Attach project account to hashed user ip in cache for 5 minutes (needed during social auth callback)
        Cache::put(
            sprintf('project-account:%s', md5($this->getIP($request))),
            $projectAccount->id,
            300,
        );

        // TODO: Handle wallet auth differently

        // Redirect to social auth
        return Socialite::driver($authProvider)
            ->redirect();
    }

    public function check(Request $request): JsonResponse
    {
        try {

            // Load project account by reference
            $projectAccount = ProjectAccount::query()
                ->where('reference', $request->get('reference'))
                ->first();
            if (!$projectAccount) {
                return response()->json([
                    'error' => __('Not Found'),
                    'reason' => __('Could not find project account by reference'),
                ], 404);
            }

            // Success
            return response()->json([
                'reference' => $projectAccount->reference,
                'auth_provider' => $projectAccount->auth_provider,
                'auth_provider_id' => $projectAccount->auth_provider_id,
                'auth_name' => $projectAccount->auth_name,
                'auth_email' => $projectAccount->auth_email,
                'auth_avatar' => $projectAccount->auth_avatar,
                'auth_country_code' => $projectAccount->auth_country_code,
                'authenticated_at' => $projectAccount->authenticated_at->toDateTimeString(),
                'is_authenticated' => !empty($projectAccount->authenticated_at),
            ]);

        } catch (Throwable $exception) {

            // Log exception
            $this->logException('Failed to handle auth check', $exception, [
                'request' => $request->toArray(),
            ]);

            // Handle error
            return response()->json([
                'error' => __('Internal Server Error'),
                'reason' => __('An unknown error occurred, please notify server administrator'),
            ], 500);

        }
    }
}
