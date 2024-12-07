<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\HydraDoomEventParserJob;
use App\Models\EventData;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Events
 */
class EventsController extends Controller
{
    /**
     * Record Event
     *
     * @header x-public-api-key 414f7c5c-b932-4d26-9570-1c2f954b64ed
     * @header x-private-api-key 3e070a66-cb1d-4f2c-930a-ee13ec7c9529
     *
     * @response status=200 scenario="OK" [No Content]
     * @response status=429 scenario="Too Many Requests" [No Content]
     * @responseFile status=422 scenario="Validation Failed" resources/api-responses/422.json
     * @responseFile status=500 scenario="Internal Server Error" resources/api-responses/500.json
     */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            // Unique event id. Must not exceed 128 characters. Example: aad91eb6-924d-43c6-a78f-04d7ddbbc382
            'event_id' => ['required', 'string', 'max:128'],
            // Unix timestamp when the event occurred. Example: 1732230013
            'timestamp' => ['required', 'integer', 'min:0'],
            // Event data payload. Example: Arbitrary data structure here
            'data' => ['required'],
        ]);

        try {

            // // Hotfix: Hydra doom is not sending is_qualifier flag in game_started event (so add it manually)
            // if ($validated['data']['type'] === 'game_started') {
            //     $validated['data']['is_qualifier'] = true;
            // }

            $eventData = EventData::create([
                'project_id' => $request->project->id,
                'event_id' => $validated['event_id'],
                'timestamp' => $validated['timestamp'],
                'data' => $validated['data'],
            ]);

            dispatch(new HydraDoomEventParserJob($eventData));

        } catch (UniqueConstraintViolationException) {}

        return response()->noContent(200);
    }
}
