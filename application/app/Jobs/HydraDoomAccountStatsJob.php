<?php

namespace App\Jobs;

use App\Models\ProjectAccount;
use App\Models\ProjectAccountStats;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class HydraDoomAccountStatsJob implements ShouldQueue
{
    use Queueable;

    const TYPE_NEW_GAME = 'new_game';
    const TYPE_GAME_STARTED = 'game_started';
    const TYPE_PLAYER_JOINED = 'player_joined';
    const TYPE_GAME_FINISHED = 'game_finished';
    const TYPE_KILL = 'kill';
    const TYPE_DEATH = 'death';
    const TYPE_SUICIDE = 'suicide';

    const DEFAULT_STATS = [
        self::TYPE_NEW_GAME => 0,
        self::TYPE_GAME_STARTED => 0,
        self::TYPE_PLAYER_JOINED => 0,
        self::TYPE_GAME_FINISHED => 0,
        self::TYPE_KILL => 0,
        self::TYPE_DEATH => 0,
        self::TYPE_SUICIDE => 0,
    ];

    private int $projectId;
    private string $reference;

    /**
     * Create a new job instance.
     */
    public function __construct(int $projectId, string $reference)
    {
        $this->projectId = $projectId;
        $this->reference = $reference;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find project account by reference
        $projectAccount = ProjectAccount::query()
            ->where('project_id', $this->projectId)
            ->with('sessionEvents.eventData')
            ->whereHas('sessions', function ($query) {
                $query->where('reference', $this->reference);
            })
            ->first();
        if (!$projectAccount) {
            return;
        }

        // Aggregate stats
        $projectAccountStats = [
            'overview' => self::DEFAULT_STATS,
            'qualifier' => self::DEFAULT_STATS,
            'elimination' => self::DEFAULT_STATS,
        ];
        $projectAccount->sessionEvents->each(function ($sessionEvent) use (&$projectAccountStats) {
            $projectAccountStats['overview'][$sessionEvent->event_type]++;
            if (isset($sessionEvent?->eventData?->data['is_qualifier']) && $sessionEvent->eventData->data['is_qualifier'] === true) {
                $projectAccountStats['qualifier'][$sessionEvent->event_type]++;
            }
            if (isset($sessionEvent?->eventData?->data['is_elimination']) && $sessionEvent->eventData->data['is_elimination'] === true) {
                $projectAccountStats['qualifier'][$sessionEvent->event_type]++;
            }
        });

        // Upsert project account stats
        ProjectAccountStats::upsert([[
            'project_id' => $this->projectId,
            'project_account_id' => $projectAccount->id,
            'stats' => json_encode($projectAccountStats),
        ]], uniqueBy: ['project_account_id', 'project_account_id'], update: ['stats']);

        // Cache data
        Cache::put(
            sprintf('project-account-global-stats:%d-%d', $projectAccount->project_id, $projectAccount->id),
            $projectAccountStats,
            3600,
        );

    }
}
