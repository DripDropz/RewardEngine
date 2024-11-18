<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed only on the local dev and staging environment
        if (app()->environment('local', 'staging')) {

            // Seed test user
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@local.dev',
            ]);

            // Seed test project
            Project::factory()->create([
                'user_id' => $user->id,
            ]);

        }
    }
}
