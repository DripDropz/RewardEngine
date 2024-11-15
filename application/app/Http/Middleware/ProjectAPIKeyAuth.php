<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectAPIKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response|JsonResponse
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $project = Project::query()
            ->where('public_api_key', $request->header('x-public-api-key'))
            ->first();

        if ($project && $project->private_api_key === $request->header('x-private-api-key')) {
            return $next($request);
        }

        return response()->json([
            'error' => __('Unauthorized'),
            'reason' => __('Invalid project public/private api key'),
        ], 401);
    }
}
