<?php

use App\Console\Commands\SyncHydraDoomGlobalStatsCommand;
use Illuminate\Support\Facades\Schedule;

// Sync hydra doom global stats every minute
Schedule::command(SyncHydraDoomGlobalStatsCommand::class)->everyMinute();
