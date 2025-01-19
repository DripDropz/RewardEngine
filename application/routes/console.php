<?php

use App\Console\Commands\GenerateHydraDoomLeaderboardCommand;
use App\Console\Commands\SyncHydraDoomGlobalStatsCommand;
use Illuminate\Support\Facades\Schedule;

// Sync hydra doom global stats every minute
// Schedule::command(SyncHydraDoomGlobalStatsCommand::class)->everyMinute();

// Generate hydra doom leaderboard every five minutes
// Schedule::command(GenerateHydraDoomLeaderboardCommand::class)->everyFiveMinutes();
