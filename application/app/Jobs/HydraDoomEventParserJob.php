<?php

namespace App\Jobs;

use App\Models\EventData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class HydraDoomEventParserJob implements ShouldQueue
{
    use Queueable;

    private EventData $eventData;

    /**
     * Create a new job instance.
     */
    public function __construct(EventData $eventData)
    {
        $this->eventData = $eventData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: HydraDoomEventParserJob implementation
        \Illuminate\Support\Facades\Log::info(
            'HydraDoomEventParserJob',
            $this->eventData->toArray(),
        );
    }
}
