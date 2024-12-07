<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlagQualifiersHydraDoomEventsCommand extends Command
{
    // const QUALIFIER_DATE_START = '2024-12-03 00:00:00';
    const QUALIFIER_DATE_START = '2024-11-01 00:00:00';
    const QUALIFIER_DATE_END = '2024-12-03 23:59:59';
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

        // For each qualified player
        foreach ($qualifiedPlayersByKillCount as $qualifiedPlayer)
        {
            // Load qualified player session events
            $projectAccountSessionEvents = $this->loadProjectAccountSessionEventsWithinQualificationPeriod($qualifiedPlayer->project_account_id);

            // Check if player played for required minutes
            if ($this->playerPlayedRequiredMinutes($projectAccountSessionEvents))
            {
                // Player qualified
                dd('qualified!');
            }
            dd('not qualified!');
        }
    }

    private function loadQualifiedPlayersByKillCount(): array
    {
        $sql = <<<QUERY
select project_account_id
from project_account_stats
where CAST(JSON_EXTRACT(stats, '$.qualifier.kill') AS UNSIGNED) >= ?
QUERY;

        return DB::select($sql, [self::REQUIRED_KILL_COUNT]);
    }

    private function loadProjectAccountSessionEventsWithinQualificationPeriod(int $projectAccountId): array
    {
        $sql = <<<QUERY
select
    project_account_session_events.event_type,
    project_account_session_events.event_timestamp,
    project_account_session_events.game_id
from project_account_sessions
join project_account_session_events on project_account_session_events.reference = project_account_sessions.reference
where project_account_sessions.project_account_id = ?
  and project_account_session_events.event_type IN ('game_started', 'game_finished')
  and (project_account_session_events.event_timestamp >= ? AND project_account_session_events.event_timestamp <= ?)
order by event_timestamp asc;
QUERY;

        return DB::select($sql, [
            $projectAccountId,
            strtotime(self::QUALIFIER_DATE_START) * 1000,
            strtotime(self::QUALIFIER_DATE_END) * 1000
        ]);
    }

    private function playerPlayedRequiredMinutes(array $projectAccountSessionEvents): bool
    {
        $groupedProjectAccountSessionEvents = [];

        foreach ($projectAccountSessionEvents as $projectAccountSessionEvent) {
            if (!isset($groupedProjectAccountSessionEvents[$projectAccountSessionEvent->game_id])) {
                $groupedProjectAccountSessionEvents[$projectAccountSessionEvent->game_id] = [
                    'game_started' => 0,
                    'game_finished' => 0,
                ];
            }
            if ($projectAccountSessionEvent->event_type === 'game_started') {
                $groupedProjectAccountSessionEvents[$projectAccountSessionEvent->game_id]['game_started'] = (int) $projectAccountSessionEvent->event_timestamp;
            }
            if ($projectAccountSessionEvent->event_type === 'game_finished') {
                $groupedProjectAccountSessionEvents[$projectAccountSessionEvent->game_id]['game_finished'] = (int) $projectAccountSessionEvent->event_timestamp;
            }
        }

        $target = (60 * self::REQUIRED_PLAY_MINUTES);
        foreach ($groupedProjectAccountSessionEvents as $groupedProjectAccountSessionEvent) {
            $gameStarted = round($groupedProjectAccountSessionEvent['game_started'] / 1000);
            $gameFinished = round($groupedProjectAccountSessionEvent['game_finished'] / 1000);
            $delta = (int) ($gameFinished - $gameStarted);
            if ($delta >= $target) {
                return true;
            }
        }

        return false;
    }
}
