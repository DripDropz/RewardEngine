<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\HydraDoomAccountStatsJob;
use App\Models\Project;
use App\Models\ProjectAccount;
use App\Models\ProjectAccountSession;
use App\Models\ProjectAccountStats;
use App\Traits\GEOBlockTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        // Load cached global stats
        $globalStats = Cache::get(sprintf('project-%d:global-stats', $project->id));
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

        // Find project account by reference
        $projectAccount = ProjectAccount::query()
            ->where('project_id', $project->id)
            ->whereHas('sessions', function ($query) use ($reference) {
                $query->where('reference', $reference);
            })
            ->first();
        if (!$projectAccount) {
            return response()->json([
                'error' => __('Unauthorized'),
                'reason' => __('Access not permitted'),
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
}
