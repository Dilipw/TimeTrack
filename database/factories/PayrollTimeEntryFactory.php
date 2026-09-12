<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TimeEntryType;
use App\Models\Payroll;
use App\Models\PayrollTimeEntry;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollTimeEntry>
 */
class PayrollTimeEntryFactory extends Factory
{
    protected $model = PayrollTimeEntry::class;

    public function definition(): array
    {
        return [
            'payroll_id' => null,
            'time_entry_id' => null,

            'working_minutes' => 0,

            'entry_type' => TimeEntryType::REGULAR,

            'hourly_rate' => 0,

            'amount' => 0,
        ];
    }

    public function forPayroll(Payroll $payroll): static
    {
        return $this->state(fn (): array => [
            'payroll_id' => $payroll->id,
        ]);
    }

    public function forTimeEntry(TimeEntry $timeEntry): static
    {
        return $this->state(fn (): array => [
            'time_entry_id' => $timeEntry->id,
            'working_minutes' => $timeEntry->working_minutes,
            'entry_type' => $timeEntry->entry_type,
        ]);
    }

    public function overtime(): static
    {
        return $this->state(fn (): array => [
            'entry_type' => TimeEntryType::OVERTIME,
        ]);
    }
}