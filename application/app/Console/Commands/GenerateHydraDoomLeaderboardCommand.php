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
    private const STATS_TYPE_OVERVIEW = 'overview';
    private const STATS_TYPE_QUALIFIER = 'qualifier';

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
                'overview' => [
                    'summary' => $this->generateSummaryLeaderboard(self::STATS_TYPE_OVERVIEW),
                    'kills' => $this->generateKillsLeaderboard(self::STATS_TYPE_OVERVIEW),
                    'deaths' => $this->generateDeathsLeaderboard(self::STATS_TYPE_OVERVIEW),
                    'suicides' => $this->generateSuicidesLeaderboard(self::STATS_TYPE_OVERVIEW),
                    'killDeathRatio' => $this->generateKillDeathRatioLeaderboard(self::STATS_TYPE_OVERVIEW),
                ],
                'qualifier' => [
                    'summary' => $this->generateSummaryLeaderboard(self::STATS_TYPE_QUALIFIER),
                    'kills' => $this->generateKillsLeaderboard(self::STATS_TYPE_QUALIFIER),
                    'deaths' => $this->generateDeathsLeaderboard(self::STATS_TYPE_QUALIFIER),
                    'suicides' => $this->generateSuicidesLeaderboard(self::STATS_TYPE_QUALIFIER),
                    'killDeathRatio' => $this->generateKillDeathRatioLeaderboard(self::STATS_TYPE_QUALIFIER),
                ],
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

    private function generateSummaryLeaderboard(string $statsType): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.{$statsType}.kill') AS total_kills,
    JSON_EXTRACT(stats, '$.{$statsType}.death') AS total_deaths,
    JSON_EXTRACT(stats, '$.{$statsType}.suicide') AS total_suicides,
    JSON_EXTRACT(stats, '$.{$statsType}.game_started') AS total_game_starts,
    JSON_EXTRACT(stats, '$.{$statsType}.player_joined') AS total_player_joins,
    JSON_EXTRACT(stats, '$.{$statsType}.game_finished') AS total_game_starts,
    IF (JSON_EXTRACT(stats, '$.{$statsType}.death') > 0, ROUND(JSON_EXTRACT(stats, '$.{$statsType}.kill') / JSON_EXTRACT(stats, '$.{$statsType}.death'), 2), JSON_EXTRACT(stats, '$.{$statsType}.kill')) AS kill_death_ratio
FROM project_account_stats
JOIN project_accounts on project_account_stats.project_account_id = project_accounts.id
WHERE project_account_stats.project_id = ?
ORDER BY total_kills DESC
LIMIT 10;
QUERY;

        return $this->transformRows(DB::select($sql, [self::PROJECT_ID]));
    }

    private function generateKillsLeaderboard(string $statsType): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.{$statsType}.kill') AS total_kills
FROM project_account_stats
JOIN project_accounts on project_account_stats.project_account_id = project_accounts.id
WHERE project_account_stats.project_id = ?
ORDER BY total_kills DESC
LIMIT 10;
QUERY;

        return $this->transformRows(DB::select($sql, [self::PROJECT_ID]));
    }

    private function generateDeathsLeaderboard(string $statsType): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.{$statsType}.death') AS total_deaths
FROM project_account_stats
JOIN project_accounts on project_account_stats.project_account_id = project_accounts.id
WHERE project_account_stats.project_id = ?
ORDER BY total_deaths DESC
LIMIT 10;
QUERY;

        return $this->transformRows(DB::select($sql, [self::PROJECT_ID]));
    }

    private function generateSuicidesLeaderboard(string $statsType): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.{$statsType}.suicide') AS total_suicides
FROM project_account_stats
JOIN project_accounts on project_account_stats.project_account_id = project_accounts.id
WHERE project_account_stats.project_id = ?
ORDER BY total_suicides DESC
LIMIT 10;
QUERY;

        return $this->transformRows(DB::select($sql, [self::PROJECT_ID]));
    }

    private function generateKillDeathRatioLeaderboard(string $statsType): array
    {
        $sql = <<<QUERY
SELECT
    project_accounts.auth_name,
    project_accounts.auth_avatar,
    JSON_EXTRACT(stats, '$.{$statsType}.kill') AS total_kills,
    JSON_EXTRACT(stats, '$.{$statsType}.death') AS total_deaths,
    IF (JSON_EXTRACT(stats, '$.{$statsType}.death') > 0, ROUND(JSON_EXTRACT(stats, '$.{$statsType}.kill') / JSON_EXTRACT(stats, '$.{$statsType}.death'), 2), JSON_EXTRACT(stats, '$.{$statsType}.kill')) AS kill_death_ratio
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
