<?php

namespace App\Http\Controllers\API;

use App\Enums\AuthProviderType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAccountSession;
use App\Traits\GEOBlockTrait;
use App\Traits\IPHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
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
        if (empty($request->get('reference')) || strlen($request->get('reference')) > 512) {
            return response()->json([
                'error' => __('Bad Request'),
                'reason' => __('The reference query string parameter is empty or larger than 512 characters.'),
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
                'reason' => __('Access not permitted'),
            ], 401);
        }

        // Ensure the reference is unique across project account sessions
        $project->load(['sessions' => function ($query) use ($request) {
            $query->where('reference', $request->get('reference'));
        }]);
        if ($project->sessions->count()) {
            return response()->json([
                'error' => __('Bad Request'),
                'reason' => __('The reference must be unique.'),
            ], 400);
        }

        // Handle wallet auth provider
        if ($authProvider === AuthProviderType::WALLET->value) {
            // TODO: Handle wallet auth differently
            return response()->json([
                'error' => __('Not Implemented'),
                'reason' => __('Wallet is not supported yet'),
            ], 400);
        }

        // Handle social auth provider
        return Socialite::driver($authProvider)
            ->redirect()
            ->withCookies([
                Cookie::make(
                    $authProvider . '_auth_attempt',
                    sprintf('%s#:rewardengine:#%s', $request->get('reference'), $publicApiKey),
                ),
            ]);

    }

    public function check(string $publicApiKey, Request $request): JsonResponse
    {
        try {

            // Check if reference is provided in the request
            if (empty($request->get('reference')) || strlen($request->get('reference')) > 512) {
                return response()->json([
                    'error' => __('Bad Request'),
                    'reason' => __('The reference query string parameter is empty or larger than 512 characters.'),
                ], 400);
            }

            // Check and cache the result for 10 seconds
            $result = Cache::remember(sprintf('auth-check:%s', md5($publicApiKey . $request->get('reference'))), 10, function () use ($publicApiKey, $request) {

                // Load session and account info
                $projectAccountSession = ProjectAccountSession::query()
                    ->where('reference', $request->get('reference'))
                    ->with('account', 'project')
                    ->whereHas('project', static function ($query) use ($publicApiKey) {
                        $query->where('public_api_key', $publicApiKey);
                    })
                    ->first();

                // Determine if authenticated
                $isAuthenticated  = (
                    $projectAccountSession &&
                    (int) $projectAccountSession->authenticated_at->diffInSeconds(now()) <= $projectAccountSession->project->session_valid_for_seconds
                );

                // Check if this request should be geo-blocked
                if ($isAuthenticated && $this->isGEOBlocked($projectAccountSession->project, $request)) {

                    // Invalidate the isAuthenticated state
                    $isAuthenticated = false;

                }

                // Build result
                return [
                    'authenticated' => $isAuthenticated,
                    'account' => $isAuthenticated ? [
                        'auth_provider' => $projectAccountSession->account->auth_provider,
                        'auth_provider_id' => $projectAccountSession->account->auth_provider_id,
                        'auth_name' => $projectAccountSession->account->auth_name,
                        'auth_email' => $projectAccountSession->account->auth_email,
                        'auth_avatar' => $projectAccountSession->account->auth_avatar,
                    ] : null,
                    'session' => $isAuthenticated ? [
                        'reference' => $projectAccountSession->reference,
                        'session_id' => $projectAccountSession->session_id,
                        'auth_country_code' => $projectAccountSession->auth_country_code,
                        'authenticated_at' => $projectAccountSession->authenticated_at->toDateTimeString(),
                    ] : null,
                ];

            });

            return response()->json($result);

        } catch (Throwable $exception) {

            // Log exception
            $this->logException('Failed to handle auth check', $exception, [
                'publicApiKey' => $publicApiKey,
                'authReference' => $request->get('reference'),
            ]);

            // Handle error
            return response()->json([
                'error' => __('Internal Server Error'),
                'reason' => __('An unknown error occurred, please notify server administrator'),
            ], 500);

        }
    }
}
