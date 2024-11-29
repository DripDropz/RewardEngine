<?php

namespace App\Http\Controllers\API;

use App\Enums\AuthProviderType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAccount;
use App\Models\ProjectAccountSession;
use App\Traits\GEOBlockTrait;
use App\Traits\IPTrait;
use App\Traits\WalletAuthTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * @group Authentication
 */
class AuthController extends Controller
{
    use IPTrait, GEOBlockTrait, WalletAuthTrait;

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
    public function init(string $publicApiKey, string $authProvider, Request $request): RedirectResponse|JsonResponse|Response
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

        /**
         * Wallet Auth
         */

        // Handle wallet auth provider type differently
        if ($authProvider === AuthProviderType::WALLET->value) {

            // Show wallet connect screen
            return Inertia::render('Wallet/Select', [
                'publicApiKey' => $publicApiKey,
                'projectName' => $project->name,
                'reference' => $request->get('reference'),
            ]);

        }

        /**
         * Social Auth
         */

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

    public function initWallet(string $publicApiKey, Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'reference' => ['required', 'string', 'min:3', 'max:512'],
            'stakeKeyAddress' => ['required', 'string', 'min:56', 'max:128'],
        ]);

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

        // Generate a wallet auth attempt
        $cacheKey = sprintf('wallet-auth-challenge-hex:%d-%s', $project->id, $request->get('stakeKeyAddress'));
        $walletAuthChallengeHex = Cache::remember($cacheKey, 180, function () use ($request) {
            return $this->buildWalletChallengeHex(
                expiration: now()->addSeconds(180),
                issued: now(),
                nonce: Str::uuid()->toString(),
                stakeKeyAddress: $request->get('stakeKeyAddress'),
            );
        });

        // Debug test
        return response()->json([
            'walletAuthChallengeHex' => $walletAuthChallengeHex,
        ]);
    }

    public function verifyWallet(string $publicApiKey, Request $request): JsonResponse
    {
        // Validate request
        $isHardwareWallet = (bool) $request->get('isHardwareWallet');
        $rules = [
            'walletName' => ['required', 'string', 'min:3', 'max:128'],
            'reference' => ['required', 'string', 'min:3', 'max:512'],
            'stakeKeyAddress' => ['required', 'string', 'min:56', 'max:128'],
            'isHardwareWallet' => ['required', 'bool'],
            'networkMode' => ['required', 'in:0,1'],
        ];
        if ($isHardwareWallet === true) {
            $rules['transactionCbor'] = ['required', 'string'];
        } else {
            $rules['signatureCbor'] = ['required', 'string'];
            $rules['signatureKey'] = ['required', 'string'];
        }
        $request->validate($rules);

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

        // Retrieve the wallet auth challenge hex
        $cacheKey = sprintf('wallet-auth-challenge-hex:%d-%s', $project->id, $request->get('stakeKeyAddress'));
        $walletAuthChallengeHex = Cache::get($cacheKey);
        if (empty($walletAuthChallengeHex)) {
            return response()->json([
                'error' => __('Gone'),
                'reason' => __('Wallet auth challenge has expired.'),
            ], 410);
        }

        // Validate the verification signature/transaction
        $isValid = $isHardwareWallet
            ? $this->verifyWalletChallengeTransaction(
                $request->get('transactionCbor'),
                $walletAuthChallengeHex,
                $request->get('stakeKeyAddress'),
            )
            : $this->verifyWalletChallengeSignature(
                $request->get('signatureCbor'),
                $request->get('signatureKey'),
                $walletAuthChallengeHex,
                $request->get('stakeKeyAddress'),
                $request->get('networkMode'),
            );
        if (!$isValid) {
            return response()->json([
                'error' => __('Unauthorized'),
                'reason' => __('Could not verify signature.'),
            ], 401);
        }

        // Upsert project account
        $projectAccount = ProjectAccount::query()
            ->where('project_id', $project->id)
            ->where('auth_provider', AuthProviderType::WALLET->value)
            ->where('auth_provider_id', $request->get('stakeKeyAddress'))
            ->first();
        if (!$projectAccount) {
            $projectAccount = new ProjectAccount;
            $projectAccount->fill([
                'project_id' => $project->id,
                'auth_provider' => AuthProviderType::WALLET->value,
                'auth_provider_id' => $request->get('stakeKeyAddress'),
            ]);
        }
        $projectAccount->auth_wallet = $request->get('walletName');
        $projectAccount->auth_name = $this->resolveAdaHandle($request->get('stakeKeyAddress'));
        $projectAccount->auth_avatar = sprintf(
            'https://api.dicebear.com/9.x/pixel-art/svg?seed=%s',
            $request->get('stakeKeyAddress'),
        );
        $projectAccount->save();

        // Record project account session
        $projectAccountSession = new ProjectAccountSession;
        $projectAccountSession->fill([
            'project_account_id' => $projectAccount->id,
            'reference' => $request->get('reference'),
            'session_id' => Str::uuid(),
            'auth_country_code' => $this->getIPCountryCode($request),
            'authenticated_at' => now(),
        ]);
        $projectAccountSession->save();

        // Setup new wallet
        if (empty($projectAccount->generated_wallet_mnemonic)) {
            $newWallet = $this->generateNewWallet();
            if ($newWallet) {
                $projectAccount->update([
                    'generated_wallet_mnemonic' => $newWallet['mnemonic'],
                    'generated_wallet_stake_address' => $newWallet['wallet']['rewardAddress'],
                ]);
            }
        }

        // Success
        return response()->json([
            'authAvatar' => $projectAccount->auth_avatar,
            'authName' => $projectAccount->auth_name,
        ]);
    }

    /**
     * Check Authentication Status
     *
     * @urlParam publicApiKey string required The project's public api key. Example: 414f7c5c-b932-4d26-9570-1c2f954b64ed
     * @queryParam reference string required Unique user/session identifier in your application that was used in the initialization step. Example: abcd1234
     *
     * @response status=200 scenario="OK - Authenticated" {"authenticated":true,"account":{"auth_provider":"google","auth_provider_id":"117571893339073554831","auth_wallet":"eternl","auth_name":"Latheesan","auth_email":"latheesan@example.com","auth_avatar":"https://example.com/profile.jpg"},"session":{"reference":"your-app-identifier-123","session_id":"265dfd21-0fa2-4895-9277-87d2ed74a294","auth_country_code":"GB","authenticated_at":"2024-11-21 22:46:16"}}
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
                    ->with(['account', 'project'])
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
                        'auth_wallet' => $projectAccountSession->account->auth_wallet,
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
                'reference' => $request->get('reference'),
            ]);

            // Handle error
            return response()->json([
                'error' => __('Internal Server Error'),
                'reason' => __('An unknown error occurred, please notify server administrator'),
            ], 500);

        }
    }

    public function refresh(string $publicApiKey, Request $request): JsonResponse
    {
        try {

            // Check if reference is provided in the request
            if (empty($request->input('session_id')) || strlen($request->input('session_id')) > 64) {
                return response()->json([
                    'error' => __('Bad Request'),
                    'reason' => __('The session_id field is empty or larger than 64 characters.'),
                ], 400);
            }

            // Check if new_reference is provided in the request
            if (empty($request->input('new_reference')) || strlen($request->input('new_reference')) > 512) {
                return response()->json([
                    'error' => __('Bad Request'),
                    'reason' => __('The new_reference field is empty or larger than 512 characters.'),
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

            // Load specific project account session
            $projectAccountSession = ProjectAccountSession::query()
                ->where('session_id', $request->input('session_id'))
                ->with('account')
                ->first();
            if (!$projectAccountSession || (int) $projectAccountSession->authenticated_at->diffInSeconds(now()) > $project->session_valid_for_seconds) {
                return response()->json([
                    'error' => __('Unauthorized'),
                    'reason' => __('Invalid session id or session expired'),
                ], 401);
            }

            // Ensure the new_reference is unique across project account sessions
            $project->load(['sessions' => function ($query) use ($request) {
                $query->where('reference', $request->input('new_reference'));
            }]);
            if ($project->sessions->count()) {
                return response()->json([
                    'error' => __('Bad Request'),
                    'reason' => __('The reference must be unique.'),
                ], 400);
            }

            // Create new project account session
            $newProjectAccountSession = new ProjectAccountSession;
            $newProjectAccountSession->fill([
                'project_account_id' => $projectAccountSession->account->id,
                'reference' => $request->input('new_reference'),
                'session_id' => Str::uuid(),
                'auth_country_code' => $this->getIPCountryCode($request),
                'authenticated_at' => now(),
            ]);
            $newProjectAccountSession->save();

            // Success
            return response()->json([
                'authenticated' => true,
                'account' => [
                    'auth_provider' => $projectAccountSession->account->auth_provider,
                    'auth_provider_id' => $projectAccountSession->account->auth_provider_id,
                    'auth_wallet' => $projectAccountSession->account->auth_wallet,
                    'auth_name' => $projectAccountSession->account->auth_name,
                    'auth_email' => $projectAccountSession->account->auth_email,
                    'auth_avatar' => $projectAccountSession->account->auth_avatar,
                ],
                'session' => [
                    'reference' => $newProjectAccountSession->reference,
                    'session_id' => $newProjectAccountSession->session_id,
                    'auth_country_code' => $newProjectAccountSession->auth_country_code,
                    'authenticated_at' => $newProjectAccountSession->authenticated_at->toDateTimeString(),
                ],
            ]);

        } catch (Throwable $exception) {

            // Log exception
            $this->logException('Failed to handle auth refresh', $exception, [
                'publicApiKey' => $publicApiKey,
                'sessionId' => $request->input('session_id'),
                'newReference' => $request->input('new_reference'),
            ]);

            // Handle error
            return response()->json([
                'error' => __('Internal Server Error'),
                'reason' => __('An unknown error occurred, please notify server administrator'),
            ], 500);

        }
    }
}
