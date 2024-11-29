<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateHydraDoomLeaderboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-hydra-doom-leaderboard-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates leaderboard based on project account session stats';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Note:
        // generate the leaderboard stats and cache it for 15 minutes
        // make the data also available via api
        // build a public facing frontend in vue to display the data
        // Update the sql query to join the project_accounts table to get their `auth_name` (Player)

        // TODO: in future PR
    }
}
