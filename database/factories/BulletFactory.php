<?php

namespace Database\Factories;

use App\Enums\BulletStatus;
use App\Enums\BulletType;
use App\Models\Bullet;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bullet>
 */
class BulletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'type' => BulletType::Task,
            'body' => fake()->sentence(),
            'status' => BulletStatus::Active,
            'expires_at' => now()->addHours(72),
        ];
    }

    public function task(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => BulletType::Task,
        ]);
    }

    public function note(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => BulletType::Note,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BulletStatus::Completed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BulletStatus::Cancelled,
        ]);
    }

    public function migrated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BulletStatus::Migrated,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(1),
        ]);
    }
}
