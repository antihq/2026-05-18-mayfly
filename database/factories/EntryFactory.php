<?php

namespace Database\Factories;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'type' => EntryType::Task,
            'content' => fake()->sentence(),
            'is_completed' => false,
            'expires_at' => now()->addHours(72),
        ];
    }

    public function task(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntryType::Task,
        ]);
    }

    public function note(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntryType::Note,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(1),
        ]);
    }
}
