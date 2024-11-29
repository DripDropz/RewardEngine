<?php

namespace App\Jobs;

use App\Models\EventData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class HydraDoomEventParserJob implements ShouldQueue
{
    use Queueable;

    const TYPE_GLOBAL = 'global';
    const TYPE_NEW_GAME = 'new_game';
    const TYPE_PLAYER_JOINED = 'player_joined';
    const TYPE_GAME_FINISHED = 'game_finished';
    const TYPE_KILL = 'kill';

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
        if (!isset($this->eventData->data['type'])) {
            return;
        }

        switch ($this->eventData->data['type']) {
            case self::TYPE_GLOBAL: $this->processGlobalEvent(); break;
            default: echo sprintf('Unknown Event Type: %s', $this->eventData->data['type']); break;
        }
    }

    private function processGlobalEvent(): void
    {
        Cache::put(
            sprintf('project-%d:global-stats', $this->eventData->project_id),
            $this->eventData->data['stats'],
        );
    }
}
