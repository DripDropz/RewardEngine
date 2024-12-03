<?php

namespace App\Console\Commands;

use App\Jobs\HydraDoomAccountStatsJob;
use App\Models\EventData;
use App\Traits\LogExceptionTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class FixGameStartedHydraDoomEventsCommand extends Command
{
    use LogExceptionTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-game-started-hydra-doom-events-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all game_started events with missing is_qualifier flag';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {

            // Find all game_started event data with missing is_qualifier flag
            $gameStartedSql = <<<QUERY
select *
from event_data
where JSON_EXTRACT(data, '$.type') = 'game_started'
  AND JSON_EXTRACT(data, '$.is_qualifier') IS NULL
  AND last_error IS NULL
QUERY;
            $gameStarted = DB::select($gameStartedSql);

            // Fix all the events
            $foundCount = count($gameStarted);
            $fixedCount = 0;
            foreach ($gameStarted as $gameStartedEvent) {

                // Decode game_started event data
                $gameStartedEventData = json_decode($gameStartedEvent->data, true);
                if (empty($gameStartedEventData['game_id'])) {
                    continue;
                }

                // Find the corresponding new_game event by game_id
                $newGameSql = <<<QUERY
select *
from event_data
where JSON_EXTRACT(data, '$.type') = 'new_game'
  and JSON_EXTRACT(data, '$.game_id') = ?
  and JSON_EXTRACT(data, '$.is_qualifier') IS NOT NULL
QUERY;
                $newGame = DB::selectOne($newGameSql, [$gameStartedEventData['game_id']]);

                // Proceed is matching new_game with is_qualifier found
                if ($newGame) {

                    // Decode new_game event data
                    $newGameEventData = json_decode($newGame->data, true);

                    // Check if the new game was a qualifier
                    if (isset($newGameEventData['is_qualifier']) && $newGameEventData['is_qualifier'] === true) {

                        // Update game started
                        $gameStartedEventData['is_qualifier'] = true;
                        EventData::query()
                            ->where('id', $gameStartedEvent->id)
                            ->update([
                                'data' => json_encode($gameStartedEventData),
                            ]);

                        // Dispatch the job to re-aggregate the account stats
                        foreach ($gameStartedEventData['keys'] ?? [] as $key) {
                            dispatch(new HydraDoomAccountStatsJob($gameStartedEvent->project_id, $key));
                        }

                        // Update counter
                        $fixedCount++;

                    }

                }

            }

            // Task completed
            $this->info(sprintf(
                'Found %d records, and fixed %d records.',
                $foundCount,
                $fixedCount,
            ));

        } catch (Throwable $exception) {

            $message = 'Failed to fix all game started events';

            $this->logException($message, $exception);

            $this->error(sprintf('%s: %s', $message, $exception->getMessage()));

        }
    }
}
