<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Traits\IPHelperTrait;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ProjectApiKeyAuth
{
    use IPHelperTrait;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response|JsonResponse
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        // Generate request key
        $requestKey = sprintf('project-api-key-auth:%s', md5($this->getIP($request)));

        // Check if the request is rate limited
        if (RateLimiter::tooManyAttempts($requestKey, 5)) {
            return response()->noContent(429);
        }

        // Parse the credentials from header
        $publicApiKey = $request->header('x-public-api-key');
        $privateApiKey = $request->header('x-private-api-key');

        // Ensure credentials exists
        if (empty($publicApiKey) || empty($privateApiKey)) {
            return $this->unauthorizedResponse($requestKey);
        }

        // Generate a cache key from supplied api keys
        $cacheKey = md5($publicApiKey . $privateApiKey);

        // Validate api key credentials
        $project = Cache::remember($cacheKey, 60, function () use ($publicApiKey, $privateApiKey) {

            // Load the project
            $project = Project::query()
                ->where('public_api_key', $publicApiKey)
                ->first();

            // Check if credential is valid if project is found by public api key and the private api key matches
            if ($project && $project->private_api_key === $privateApiKey) {
                return $project;
            }

            // Not valid
            return null;

        });

        // Continue with request if we have a valid project
        if ($project) {
            $request->merge(compact('project'));
            return $next($request);
        }

        // Not authorised
        return $this->unauthorizedResponse($requestKey);
    }

    private function unauthorizedResponse(string $requestKey): JsonResponse
    {
        // Increment rate limiter count
        RateLimiter::increment($requestKey);

        // Default response
        return response()->json([
            'error' => __('Unauthorized'),
            'reason' => __('Invalid project public/private api key'),
        ], 401);
    }
}
