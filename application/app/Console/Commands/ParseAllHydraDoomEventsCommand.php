<?php

namespace App\Console\Commands;

use App\Jobs\HydraDoomEventParserJob;
use Illuminate\Console\Command;

class ParseAllHydraDoomEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:parse-all-hydra-doom-events-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse all hydra doom events';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $eventData = \App\Models\EventData::query()
            ->where('project_id', 1)
            ->get();

        foreach ($eventData as $eventDatum) {
            dispatch(new HydraDoomEventParserJob($eventDatum));
        }
    }
}
