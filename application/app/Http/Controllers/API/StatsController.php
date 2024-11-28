<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @group Stats
 */
class StatsController extends Controller
{
    /**
     * Global Stats
     *
     * @response 200 ["todo"]
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function global(string $publicApiKey): JsonResponse
    {
        // Return dummy data
        return response()
            ->json(['todo' => 'global stats']);
    }

    /**
     * Session Stats
     *
     * @response 200 ["todo"]
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function session(string $publicApiKey, string $sessionId): JsonResponse
    {
        // Return dummy data
        return response()
            ->json(['todo' => 'session stats', 'sessionId' => $sessionId]);
    }
}
