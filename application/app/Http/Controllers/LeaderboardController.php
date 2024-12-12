<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Traits\GEOBlockTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    use GEOBlockTrait;

    public function index(string $publicApiKey, Request $request): Response|JsonResponse
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

        // Render view
        return Inertia::render('Leaderboard/Index', [
            'publicApiKey' => $publicApiKey,
            'projectName' => $project->name,
        ]);
    }

    public function myAccount(string $publicApiKey, Request $request)
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

        // Settings
        $settings = [
            'commemorativeTokenAirdropRequirements' => [
                'required_kill_count' => 25,
                'required_play_minutes' => 15,
            ],
            'usdmAirdropRequirements' => [
                'required_kill_count' => 50,
                'required_play_minutes' => 60,
            ],
        ];

        // Render view
        return Inertia::render('Leaderboard/MyAccount', [
            'publicApiKey' => $publicApiKey,
            'projectName' => $project->name,
            'settings' => $settings,
        ]);
    }
}
