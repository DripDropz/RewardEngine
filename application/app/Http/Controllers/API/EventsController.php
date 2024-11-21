<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
     */
    public function store(Request $request): Response
    {
        $request->validate([
            // Unique event id. Must not exceed 128 characters. Example: aad91eb6-924d-43c6-a78f-04d7ddbbc382
            'event_id' => ['required', 'string', 'max:128'],
            // Unix timestamp when the event occurred. Example: 1732230013
            'timestamp' => ['required', 'integer', 'min:0'],
            // Event data payload. Example: Arbitrary data structure here
            'data' => ['required'],
        ]);

        try {
            $eventData = EventData::create([
                'project_id' => $request->project->id,
                'event_id' => $request->validated('event_id'),
                'timestamp' => $request->validated('timestamp'),
                'data' => json_encode($request->validated('data')),
            ]);
            // TODO dispatch(new ProcessEventDataJob($eventData));
        } catch (UniqueConstraintViolationException) {}

        return response()->noContent(200);
    }
}
