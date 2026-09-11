<?php

namespace Database\Factories;

use App\Enums\TimeEntryStatus;
use App\Enums\TimeEntryType;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        return [
            'employee_id' => null,
            'project_id' => null,
            'task_id' => null,

            'work_date' => now()->subDay()->format('Y-m-d'),

            'start_time' => '09:00:00',
            'end_time' => '18:00:00',

            'break_minutes' => 60,

            // This is normally calculated by TimeEntryService.
            // Factory provides a valid default for seeded/test records.
            'working_minutes' => 480,

            'entry_type' => TimeEntryType::REGULAR,
            'status' => TimeEntryStatus::DRAFT,

            'submitted_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TimeEntryStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TimeEntryStatus::APPROVED,
            'submitted_at' => now()->subHours(2),
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TimeEntryStatus::REJECTED,
            'submitted_at' => now()->subDay(),
            'rejected_at' => now(),
            'rejection_reason' => 'Please correct the recorded working hours.',
        ]);
    }

    public function overtime(): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_type' => TimeEntryType::OVERTIME,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TimeEntryStatus::CANCELLED,
        ]);
    }
}