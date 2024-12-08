<?php

namespace App\Http\Controllers\API;

use App\Enums\AuthProviderType;
use App\Http\Controllers\Controller;
use App\Jobs\HydraDoomAccountStatsJob;
use App\Models\Project;
use App\Models\ProjectAccount;
use App\Models\ProjectAccountSession;
use App\Models\ProjectAccountStats;
use App\Traits\GEOBlockTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * @group Stats
 */
class StatsController extends Controller
{
    use GEOBlockTrait;

    /**
     * Global Stats
     *
     * @response 200 scenario="OK" {"key1":"value1", "key2":"value3"}
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @response status=503 scenario="Service Unavailable" {"error":"Service Unavailable", "reason":"Reason for this error"}
     * @responseFile status=401 scenario="Unauthorized" resources/api-responses/401.json
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function global(string $publicApiKey, Request $request): JsonResponse
    {
        // Load project by public api key
        $project = Cache::remember(sprintf('project:%s', $publicApiKey), 600, function () use ($publicApiKey) {
            $project = Project::query()
                ->where('public_api_key', $publicApiKey)
                ->first();
            if (!$project) {
                return false;
            }
            return $project;
        });
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

        // Load cached global stats
        $globalStats = Cache::get(sprintf('project-global-stats:%d', $project->id));
        if (!$globalStats) {
            return response()->json([
                'error' => __('Service Unavailable'),
                'reason' => __('Global stats not available, try again later'),
            ], 503);
        }

        // Return Cached data
        return response()
            ->json($globalStats);
    }

    /**
     * Session Stats
     *
     * @response 200 scenario="OK" {"key1":"value1", "key2":"value3"}
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @response status=503 scenario="Service Unavailable" {"error":"Service Unavailable", "reason":"Reason for this error"}
     * @responseFile status=401 scenario="Unauthorized" resources/api-responses/401.json
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function session(string $publicApiKey, string $reference, Request $request): JsonResponse
    {
        // Load project by public api key
        $project = Cache::remember(sprintf('project:%s', $publicApiKey), 600, function () use ($publicApiKey) {
            $project = Project::query()
                ->where('public_api_key', $publicApiKey)
                ->first();
            if (!$project) {
                return false;
            }
            return $project;
        });
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

        // Find project account by reference
        $projectAccount = Cache::remember(sprintf('project-account-reference:%d-%s', $project->id, $reference), 600, function () use ($project, $reference) {
            $projectAccount = ProjectAccount::query()
                ->where('project_id', $project->id)
                ->whereHas('sessions', function ($query) use ($reference) {
                    $query->where('reference', $reference);
                })
                ->first();
            if (!$projectAccount) {
                return false;
            }
            return $projectAccount;
        });
        if (!$projectAccount) {
            return response()->json([
                'error' => __('Unauthorized'),
                'reason' => __('Invalid reference'),
            ], 401);
        }

        // Load cached data
        $cacheKey = sprintf('project-account-global-stats:%d-%d', $project->id, $projectAccount->id);
        $projectAccountStats = Cache::remember($cacheKey, 3600, function () use ($projectAccount) {
            $result = ProjectAccountStats::query()
                ->where('project_account_id', $projectAccount->id)
                ->first();
            if ($result) {
                return $result->stats;
            }
            return null;
        });
        if (!$projectAccountStats) {
            dispatch(new HydraDoomAccountStatsJob($project->id, $reference));
            return response()->json([
                'error' => __('Service Unavailable'),
                'reason' => __('Session stats not available, try again later'),
            ], 503);
        }

        // Return cached data
        return response()
            ->json($projectAccountStats);
    }

    /**
     * Session Link Wallet Address
     *
     * @urlParam publicApiKey string required The project's public api key. Example: 414f7c5c-b932-4d26-9570-1c2f954b64ed
     * @bodyParam session_id string required Previously authentication session id. Example: 069ff9f1-87ad-43b0-90a9-05493a330273
     * @bodyParam wallet_address string required The wallet address you want to link to your social account. Example: stake1upafv37jqjy8pgrjdauxyxrruqme0hqhh9ryww34mm297agc0f3vc
     *
     * @response status=204 scenario="Successfully Linked" [No Content]
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @responseFile status=400 scenario="Bad Request" resources/api-responses/400.json
     * @responseFile status=401 scenario="Unauthorized" resources/api-responses/401.json
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function sessionLinkWalletAddress(string $publicApiKey, Request $request): \Illuminate\Http\Response|JsonResponse
    {
        // Check if reference is provided in the request
        if (empty($request->input('session_id')) || strlen($request->input('session_id')) > 64) {
            return response()->json([
                'error' => __('Bad Request'),
                'reason' => __('The session_id field is empty or larger than 64 characters.'),
            ], 400);
        }

        // Check if new_reference is provided in the request
        if (empty($request->input('wallet_address'))) {
            return response()->json([
                'error' => __('Bad Request'),
                'reason' => __('The wallet_address field is empty.'),
            ], 400);
        }

        // Load project by public api key
        $project = Cache::remember(sprintf('project:%s', $publicApiKey), 600, function () use ($publicApiKey) {
            $project = Project::query()
                ->where('public_api_key', $publicApiKey)
                ->first();
            if (!$project) {
                return false;
            }
            return $project;
        });
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

        // Update linked wallet address
        $projectAccountSession->account->update([
            'linked_wallet_stake_address' => $request->input('wallet_address'),
        ]);

        // Success
        return response()->noContent();
    }

    /**
     * Session Link Discord Account
     *
     * @urlParam publicApiKey string required The project's public api key. Example: 414f7c5c-b932-4d26-9570-1c2f954b64ed
     * @urlParam sessionId string required Previously authentication session id. Example: 069ff9f1-87ad-43b0-90a9-05493a330273
     *
     * @response status=322 scenario="When successfully initialised" [Redirect]
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @responseFile status=400 scenario="Bad Request" resources/api-responses/400.json
     * @responseFile status=401 scenario="Unauthorized" resources/api-responses/401.json
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function sessionLinkDiscordAccount(string $publicApiKey, string $sessionId, Request $request): JsonResponse|RedirectResponse
    {
        // Load project by public api key
        $project = Cache::remember(sprintf('project:%s', $publicApiKey), 600, function () use ($publicApiKey) {
            $project = Project::query()
                ->where('public_api_key', $publicApiKey)
                ->first();
            if (!$project) {
                return false;
            }
            return $project;
        });
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
            ->where('session_id', $sessionId)
            ->with('account')
            ->first();
        if (!$projectAccountSession || (int) $projectAccountSession->authenticated_at->diffInSeconds(now()) > $project->session_valid_for_seconds) {
            return response()->json([
                'error' => __('Unauthorized'),
                'reason' => __('Invalid session id or session expired'),
            ], 401);
        }

        // Check if linked discord account already present
        if (!empty($projectAccountSession->account->linked_discord_account)) {
            return response()->json([
                'error' => __('Bad Request'),
                'reason' => __('Already linked'),
            ], 400);
        }

        // Redirect to discord with cookie
        return Socialite::driver(AuthProviderType::DISCORD->value)
            ->redirect()
            ->withCookies([
                Cookie::make(
                    'link_discord_account',
                    $projectAccountSession->account->id,
                ),
            ]);
    }

    /**
     * Leaderboard
     *
     * @response 200 scenario="OK" {"key1":"value1", "key2":"value3"}
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @response status=503 scenario="Service Unavailable" {"error":"Service Unavailable", "reason":"Reason for this error"}
     * @responseFile status=401 scenario="Unauthorized" resources/api-responses/401.json
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function leaderboard(string $publicApiKey, Request $request): JsonResponse
    {
        // Load project by public api key
        $project = Cache::remember(sprintf('project:%s', $publicApiKey), 600, function () use ($publicApiKey) {
            $project = Project::query()
                ->where('public_api_key', $publicApiKey)
                ->first();
            if (!$project) {
                return false;
            }
            return $project;
        });
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

        // Load cached leaderboard
        $leaderboard = Cache::get(sprintf('project-leaderboard:%d', $project->id));
        if (!$leaderboard) {
            return response()->json([
                'error' => __('Service Unavailable'),
                'reason' => __('Leaderboard not available, try again later'),
            ], 503);
        }

        // Return Cached data
        return response()
            ->json($leaderboard);
    }

    /**
     * Leaderboard Qualifiers
     *
     * @response 200 scenario="OK" {"key1":"value1", "key2":"value3"}
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @response status=503 scenario="Service Unavailable" {"error":"Service Unavailable", "reason":"Reason for this error"}
     * @responseFile status=401 scenario="Unauthorized" resources/api-responses/401.json
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function leaderboardQualifiers(string $publicApiKey, Request $request): JsonResponse
    {
        // Load project by public api key
        $project = Cache::remember(sprintf('project:%s', $publicApiKey), 600, function () use ($publicApiKey) {
            $project = Project::query()
                ->where('public_api_key', $publicApiKey)
                ->first();
            if (!$project) {
                return false;
            }
            return $project;
        });
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

        // Load from cache (or warm up cache)
        $leaderboardQualifiers = Cache::remember(sprintf('project-leaderboard-qualifiers:%d', $project->id), 60, function () use ($publicApiKey, $project) {

            // Load qualified players query
            $sql = <<<QUERY
select project_accounts.auth_provider,
       project_accounts.auth_name,
       project_accounts.auth_avatar,
       project_accounts.linked_wallet_stake_address,
       project_accounts.linked_discord_account,
       project_account_stats.qualifier
from project_account_stats
join project_accounts on project_account_stats.project_account_id = project_accounts.id
where project_account_stats.project_id = ?
  and JSON_EXTRACT(project_account_stats.qualifier, '$.is_qualified') = true
QUERY;

            // Query results
            $results = DB::select($sql, [$project->id]);

            // Format and return results
            return collect($results)->map(function ($row) {
                $row = (array) $row;
                $row['auth_name'] = decrypt($row['auth_name']);
                $row['auth_avatar'] = decrypt($row['auth_avatar']);
                $row['linked_discord_account'] = json_decode($row['linked_discord_account'], true);
                $row['qualifier'] = json_decode($row['qualifier'], true);
                return $row;
            })->toArray();

        });

        // Return Cached data
        return response()
            ->json($leaderboardQualifiers);
    }
}
