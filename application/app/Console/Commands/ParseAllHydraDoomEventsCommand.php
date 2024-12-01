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
            ->orderBy('timestamp', 'asc')
            ->get();

        $this->info(sprintf('Found Event Data: %s', $eventData->count()));

        foreach ($eventData as $index => $eventDatum) {
            if ($eventDatum->data['type'] === 'global') {
                // Skip global stats event
                continue;
            }
            (new HydraDoomEventParserJob($eventDatum))->handle();
            if (($index+1) % 1000 === 0) {
                $this->info(sprintf('Done Parsing: %s', $index+1));
            }
        }

        $this->info('Task Completed');
    }
}
