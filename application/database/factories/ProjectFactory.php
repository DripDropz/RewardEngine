<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 0,
            'name' => 'Hydra Doom Testing',
            'public_api_key' => '067d20be-8baa-49cb-b501-e004af358870',
            'private_api_key' => 'f200599d-5d54-4883-b53d-318a00a055e2',
            'geo_blocked_countries' => 'CU, IR, KP, SY, UA', // Cuba, Iran, North Korea, Syria, Ukraine
            'session_valid_for_seconds' => 3600, // 1 Hour
        ];
    }
}
