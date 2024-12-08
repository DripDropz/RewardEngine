<?php

namespace App\Console\Commands;

use App\Enums\AuthProviderType;
use App\Models\ProjectAccount;
use Illuminate\Console\Command;

class FixDiscordUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-discord-users-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update discord logged in users with correct linked account info';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $discordUsers = ProjectAccount::query()
            ->where('auth_provider', AuthProviderType::DISCORD->value)
            ->whereNull('linked_discord_account')
            ->select('id', 'auth_provider_id', 'auth_name')
            ->get();

        if ($discordUsers->count() > 0) {

            $this->info(sprintf('Found %d discord users that needs fixing', $discordUsers->count()));

            foreach ($discordUsers as $discordUser) {
                $discordUser->update([
                    'linked_discord_account' => [
                        'id' => $discordUser->auth_provider_id,
                        'name' => $discordUser->auth_name,
                    ],
                ]);
            }

            $this->info('Task Completed');

        } else {

            $this->info('Could not find any discord users that needs fixing');

        }
    }
}
