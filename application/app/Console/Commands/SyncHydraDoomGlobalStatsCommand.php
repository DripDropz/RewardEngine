<?php

namespace App\Console\Commands;

use App\Jobs\HydraDoomEventParserJob;
use App\Models\EventData;
use App\Traits\LogExceptionTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SyncHydraDoomGlobalStatsCommand extends Command
{
    use LogExceptionTrait;

    private const PROJECT_ID = 1;
    private const API_ENDPOINT = 'https://api.us-east-1.hydra-doom.sundae.fi/global_stats';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-hydra-doom-global-stats-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingests hydra doom global stats';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {

            $response = Http::timeout(60)
                ->connectTimeout(60)
                ->get(static::API_ENDPOINT)
                ->throw();

            if ($response->successful()) {

                $eventData = EventData::create([
                    'project_id' => static::PROJECT_ID,
                    'event_id' => Str::uuid()->toString(),
                    'timestamp' => time(),
                    'data' => [
                        'type' => 'global',
                        'stats' => $response->json(),
                    ],
                ]);

                dispatch(new HydraDoomEventParserJob($eventData));

            }

        } catch (Throwable $exception) {

            $message = 'Failed to ingests hydra doom global stats';

            $this->logException($message, $exception);

            $this->error(sprintf('%s: %s', $message, $exception->getMessage()));

        }
    }
}
