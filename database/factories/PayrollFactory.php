<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        return [
            'employee_id' => null,

            'period_start' => now()
                ->subMonth()
                ->startOfMonth()
                ->format('Y-m-d'),

            'period_end' => now()
                ->subMonth()
                ->endOfMonth()
                ->format('Y-m-d'),

            'hourly_rate' => 500.00,

            'regular_minutes' => 0,
            'overtime_minutes' => 0,

            'overtime_multiplier' => 1.50,

            'regular_amount' => 0,
            'overtime_amount' => 0,
            'adjustment_amount' => 0,
            'deduction_amount' => 0,
            'gross_amount' => 0,
            'net_amount' => 0,

            'status' => PayrollStatus::DRAFT,

            'finalized_at' => null,
            'finalized_by' => null,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(fn (): array => [
            'employee_id' => $employee->id,
            'hourly_rate' => $employee->hourly_rate,
        ]);
    }

    public function finalized(User $user): static
    {
        return $this->state(fn (): array => [
            'status' => PayrollStatus::FINALIZED,
            'finalized_at' => now(),
            'finalized_by' => $user->id,
        ]);
    }
}