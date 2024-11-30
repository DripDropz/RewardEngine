<?php

namespace App\Console\Commands;

use App\Traits\LogExceptionTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateHydraDoomLeaderboardCommand extends Command
{
    use LogExceptionTrait;

    private const PROJECT_ID = 1;

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
        try {

            // Generate cache key
            $cacheKey = sprintf('project-leaderboard:%d', self::PROJECT_ID);

            // Generate leaderboard
            $leaderboard = [
                'overview' => $this->generateOverviewLeaderboard(),
                'kills' => $this->generateKillsLeaderboard(),
                'deaths' => $this->generateDeathsLeaderboard(),
                'suicides' => $this->generateSuicidesLeaderboard(),
                'killDeathRatio' => $this->generateKillDeathRatioLeaderboard(),
                'generatedAt' => now()->toDateTimeString(),
            ];

            // Cache data forever
            Cache::forever($cacheKey, $leaderboard);

        } catch (Throwable $exception) {

            $message = 'Failed to generate hydra doom leaderboard';

            $this->logException($message, $exception);

            $this->error(sprintf('%s: %s', $message, $exception->getMessage()));

        }
    }

    private function generateOverviewLeaderboard(): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.kill') AS total_kills,
    JSON_EXTRACT(stats, '$.death') AS total_deaths,
    JSON_EXTRACT(stats, '$.suicide') AS total_suicides,
    JSON_EXTRACT(stats, '$.game_started') AS total_game_starts,
    JSON_EXTRACT(stats, '$.player_joined') AS total_player_joins,
    JSON_EXTRACT(stats, '$.game_finished') AS total_game_starts,
    IF (JSON_EXTRACT(stats, '$.death') > 0, ROUND(JSON_EXTRACT(stats, '$.kill') / JSON_EXTRACT(stats, '$.death'), 2), JSON_EXTRACT(stats, '$.kill')) AS kill_death_ratio
FROM project_account_stats
JOIN project_accounts on project_account_stats.project_account_id = project_accounts.id
WHERE project_account_stats.project_id = ?
ORDER BY total_kills DESC
LIMIT 10;
QUERY;

        return $this->transformRows(DB::select($sql, [self::PROJECT_ID]));
    }

    private function generateKillsLeaderboard(): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.kill') AS total_kills
FROM project_account_stats
JOIN project_accounts on project_account_stats.project_account_id = project_accounts.id
WHERE project_account_stats.project_id = ?
ORDER BY total_kills DESC
LIMIT 10;
QUERY;

        return $this->transformRows(DB::select($sql, [self::PROJECT_ID]));
    }

    private function generateDeathsLeaderboard(): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.death') AS total_deaths
FROM project_account_stats
JOIN project_accounts on project_account_stats.project_account_id = project_accounts.id
WHERE project_account_stats.project_id = ?
ORDER BY total_deaths DESC
LIMIT 10;
QUERY;

        return $this->transformRows(DB::select($sql, [self::PROJECT_ID]));
    }

    private function generateSuicidesLeaderboard(): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.suicide') AS total_suicides
FROM project_account_stats
JOIN project_accounts on project_account_stats.project_account_id = project_accounts.id
WHERE project_account_stats.project_id = ?
ORDER BY total_suicides DESC
LIMIT 10;
QUERY;

        return $this->transformRows(DB::select($sql, [self::PROJECT_ID]));
    }

    private function generateKillDeathRatioLeaderboard(): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.kill') AS total_kills,
    JSON_EXTRACT(stats, '$.death') AS total_deaths,
    IF (JSON_EXTRACT(stats, '$.death') > 0, ROUND(JSON_EXTRACT(stats, '$.kill') / JSON_EXTRACT(stats, '$.death'), 2), JSON_EXTRACT(stats, '$.kill')) AS kill_death_ratio
FROM project_account_stats
JOIN project_accounts on project_account_stats.project_account_id = project_accounts.id
WHERE project_account_stats.project_id = ?
ORDER BY kill_death_ratio DESC
LIMIT 10;
QUERY;

        return $this->transformRows(DB::select($sql, [self::PROJECT_ID]));
    }

    private function transformRows(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            $row = (array) $row;
            $row['auth_name'] = !empty($row['auth_name']) ? decrypt($row['auth_name']) : null;
            $row['auth_avatar'] = !empty(decrypt($row['auth_avatar'])) ? decrypt($row['auth_avatar']) : null;
            return $row;
        })->toArray();
    }
}
