<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class HydraDoomAccountStatsJob implements ShouldQueue
{
    use Queueable;

    private int $projectId;
    private string $reference;

    /**
     * Create a new job instance.
     */
    public function __construct(int $projectId, string $reference)
    {
        $this->reference = $reference;
        $this->projectId = $projectId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
