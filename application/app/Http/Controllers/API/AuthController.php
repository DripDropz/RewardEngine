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

/**
 * @group Authentication
 */
class AuthController extends Controller
{
    use IPHelperTrait, GEOBlockTrait;

    /**
     * List Auth Providers
     *
     * @response 200 ["wallet", "google", "twitter", "discord", "github"]
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function providers(): JsonResponse
    {
        // Return supported auth providers
        return response()
            ->json(AuthProviderType::values());
    }

    /**
     * Initialize Authentication
     *
     * @urlParam publicApiKey string required The project's public api key. Example: 414f7c5c-b932-4d26-9570-1c2f954b64ed
     * @urlParam authProvider string required The selected auth provider. Example: twitter
     * @queryParam reference string required Unique user/session identifier in your application. Example: abcd1234
     *
     * @response status=322 scenario="When successfully initialised"
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @responseFile status=400 scenario="Bad Request" resources/api-responses/400.json
     * @responseFile status=401 scenario="Unauthorized" resources/api-responses/401.json
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
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

    /**
     * Check Authentication Status
     *
     * @urlParam publicApiKey string required The project's public api key. Example: 414f7c5c-b932-4d26-9570-1c2f954b64ed
     * @queryParam reference string required Unique user/session identifier in your application that was used in the initialization step. Example: abcd1234
     *
     * @response status=200 scenario="OK - Authenticated" {"authenticated":true,"account":{"auth_provider":"google","auth_provider_id":"117571893339073554831","auth_name":"Latheesan","auth_email":"latheesan@example.com","auth_avatar":"https://example.com/profile.jpg"},"session":{"reference":"your-app-identifier-123","session_id":"265dfd21-0fa2-4895-9277-87d2ed74a294","auth_country_code":"GB","authenticated_at":"2024-11-21 22:46:16"}}
     * @response status=200 scenario="OK - Unauthenticated" {"authenticated":false,"account":null,"session":null}
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @responseFile status=400 scenario="Bad Request" resources/api-responses/400.json
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
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
