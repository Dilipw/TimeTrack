<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'project_code' => fake()->unique()->bothify('PRJ-####'),
            'name' => fake()->catchPhrase(),
            'description' => fake()->optional()->paragraph(),
            'project_manager_id' => null,
            'start_date' => now()->subMonth()->format('Y-m-d'),
            'end_date' => null,
            'status' => ProjectStatus::PLANNING,
            'budget' => fake()->optional()->randomFloat(2, 10000, 500000),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::ACTIVE,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::COMPLETED,
            'end_date' => now()->subDay()->format('Y-m-d'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::CANCELLED,
        ]);
    }
}