<?php

namespace App\Console\Commands;

use App\Models\ProjectAccountStats;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlagQualifiersHydraDoomEventsCommand extends Command
{
    const QUALIFIER_DATE_START = '2024-12-03 00:00:00';
    const QUALIFIER_DATE_END = '2024-12-04 16:00:00';
    const REQUIRED_KILL_COUNT = 25;
    const REQUIRED_PLAY_MINUTES = 15;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:flag-qualifiers-hydra-doom-events-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flag all the players who passed the qualifiers';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Find all qualified players by kill count
        $qualifiedPlayersByKillCount = $this->loadQualifiedPlayersByKillCount();

        // Debug
        $this->info(sprintf(
            'Found %d players who meet the required %d kill count',
            count($qualifiedPlayersByKillCount),
            self::REQUIRED_KILL_COUNT,
        ));

        // For each qualified player
        foreach ($qualifiedPlayersByKillCount as $qualifiedPlayer)
        {
            // Load qualified player session events
            $projectAccountSessionEvents = $this->loadProjectAccountSessionEventsWithinQualificationPeriod($qualifiedPlayer->project_account_id);

            // Calculate total play time in minutes
            $totalPlayTimeMinutes = $this->calculateTotalPlayTimeMinutes($projectAccountSessionEvents);

            // Update project account stats
            $playedDuringQualifierPeriod = count($projectAccountSessionEvents) > 0;
            $achievedRequiredKillCount = ((int) $qualifiedPlayer->total_kills >= self::REQUIRED_KILL_COUNT);
            $achievedRequiredPlayMinutes = ($totalPlayTimeMinutes >= self::REQUIRED_PLAY_MINUTES);
            ProjectAccountStats::query()
                ->where('project_id', $qualifiedPlayer->project_id)
                ->where('project_account_id', $qualifiedPlayer->project_account_id)
                ->update([
                    'qualifier' => [
                        'is_qualified' => ($playedDuringQualifierPeriod && $achievedRequiredKillCount && $achievedRequiredPlayMinutes),
                        'requirements' => [
                            [
                                'play_from' => self::QUALIFIER_DATE_START,
                                'play_to' => self::QUALIFIER_DATE_END,
                                'is_met' => $playedDuringQualifierPeriod,
                            ],
                            [
                                'required_kill_count' => self::REQUIRED_KILL_COUNT,
                                'actual_kill_count' => (int) $qualifiedPlayer->total_kills,
                                'is_met' => $achievedRequiredKillCount,
                            ],
                            [
                                'required_play_minutes' => self::REQUIRED_PLAY_MINUTES,
                                'actual_play_minutes' => $totalPlayTimeMinutes,
                                'is_met' => $achievedRequiredPlayMinutes,
                            ],
                        ],
                    ],
                ]);

            // Debug
            $this->info(sprintf(
                'Done processing project account id: %d',
                $qualifiedPlayer->project_account_id,
            ));
        }

        // Debug
        $this->info('Task Completed');
    }

    private function loadQualifiedPlayersByKillCount(): array
    {
        $sql = <<<QUERY
select project_id, project_account_id, JSON_EXTRACT(stats, '$.qualifier.kill') as `total_kills`
from project_account_stats
where CAST(JSON_EXTRACT(stats, '$.qualifier.kill') AS UNSIGNED) >= ?
QUERY;

        return DB::select($sql, [self::REQUIRED_KILL_COUNT]);
    }

    private function loadProjectAccountSessionEventsWithinQualificationPeriod(int $projectAccountId): array
    {
        $sql = <<<QUERY
select
    max(project_account_session_events.game_id) as game_id,
    min(project_account_session_events.event_timestamp) as earliest_event_timestamp,
    max(project_account_session_events.event_timestamp) as latest_event_timestamp
from project_account_sessions
join project_account_session_events on project_account_session_events.reference = project_account_sessions.reference
where project_account_sessions.project_account_id = ?
  and (project_account_session_events.event_timestamp >= ? AND project_account_session_events.event_timestamp <= ?)
group by project_account_session_events.game_id
QUERY;

        return DB::select($sql, [
            $projectAccountId,
            strtotime(self::QUALIFIER_DATE_START) * 1000,
            strtotime(self::QUALIFIER_DATE_END) * 1000
        ]);
    }

    private function calculateTotalPlayTimeMinutes(array $projectAccountSessionEvents): float
    {
        $totalPlayTimeMinutes = 0;

        foreach ($projectAccountSessionEvents as $projectAccountSessionEvent) {
            if (!empty($projectAccountSessionEvent->earliest_event_timestamp) && !empty($projectAccountSessionEvent->latest_event_timestamp)) {
                $earliestEventTimestamp = Carbon::createFromTimestampMs($projectAccountSessionEvent->earliest_event_timestamp);
                $latestEventTimestamp = Carbon::createFromTimestampMs($projectAccountSessionEvent->latest_event_timestamp);
                $totalPlayTimeMinutes += round($earliestEventTimestamp->diffInMinutes($latestEventTimestamp), 2);
            }
        }

        return $totalPlayTimeMinutes;
    }
}
