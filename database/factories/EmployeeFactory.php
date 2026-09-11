<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_code' => fake()->unique()->bothify('EMP###'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'joining_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'hourly_rate' => fake()->randomFloat(2, 150, 1000),
            'status' => EmployeeStatus::ACTIVE,
        ];
    }
}