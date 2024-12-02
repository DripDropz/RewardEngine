<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Traits\GEOBlockTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    use GEOBlockTrait;

    public function index(string $publicApiKey, Request $request): Response|JsonResponse
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

        // Render view
        return Inertia::render('Leaderboard/Index', [
            'publicApiKey' => $publicApiKey,
            'projectName' => $project->name,
        ]);
    }
}
