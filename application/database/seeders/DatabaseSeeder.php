<?php

namespace Database\Seeders;

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
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@local.dev',
            ]);

        }
    }
}
