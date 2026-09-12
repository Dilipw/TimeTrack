<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\PayrollStatus;
use App\Enums\TimeEntryStatus;
use App\Enums\TimeEntryType;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollTimeEntry;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    private const DEFAULT_OVERTIME_MULTIPLIER = '1.50';

    /**
     * Create a draft payroll for an employee and period.
     *
     * Amounts are calculated later during calculate/finalize.
     */
    public function create(
        Employee $employee,
        Carbon|string $periodStart,
        Carbon|string $periodEnd,
        array $data = [],
    ): Payroll {
        $periodStart = $this->parseDate($periodStart);
        $periodEnd = $this->parseDate($periodEnd);

        $this->ensureValidEmployee($employee);
        $this->ensureValidPeriod($periodStart, $periodEnd);
        $this->ensureNoDuplicatePayroll(
            $employee,
            $periodStart,
            $periodEnd,
        );

        $overtimeMultiplier = (string) (
            $data['overtime_multiplier']
            ?? self::DEFAULT_OVERTIME_MULTIPLIER
        );

        $adjustmentAmount = (string) (
            $data['adjustment_amount']
            ?? '0.00'
        );

        $deductionAmount = (string) (
            $data['deduction_amount']
            ?? '0.00'
        );

        $this->validateOvertimeMultiplier($overtimeMultiplier);
        $this->validateAdjustmentAmount($adjustmentAmount);
        $this->validateDeductionAmount($deductionAmount);

        return Payroll::create([
            'employee_id' => $employee->id,

            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),

            // Current rate is used for the draft.
            // Finalization takes the final historical snapshot.
            'hourly_rate' => $employee->hourly_rate,

            'regular_minutes' => 0,
            'overtime_minutes' => 0,

            'overtime_multiplier' => $overtimeMultiplier,

            'regular_amount' => 0,
            'overtime_amount' => 0,

            'adjustment_amount' => $adjustmentAmount,
            'deduction_amount' => $deductionAmount,

            'gross_amount' => 0,
            'net_amount' => 0,

            'status' => PayrollStatus::DRAFT,

            'finalized_at' => null,
            'finalized_by' => null,
        ]);
    }

    /**
     * Calculate a draft payroll using currently approved time entries.
     *
     * This method does not finalize the payroll.
     */
    public function calculate(Payroll $payroll): Payroll
    {
        return DB::transaction(function () use ($payroll): Payroll {
            $payroll = Payroll::query()
                ->lockForUpdate()
                ->findOrFail($payroll->id);

            $this->ensureDraft($payroll);

            $employee = Employee::query()
                ->findOrFail($payroll->employee_id);

            $this->ensureValidEmployee($employee);

            $periodStart = Carbon::parse($payroll->period_start);
            $periodEnd = Carbon::parse($payroll->period_end);

            $this->ensureValidPeriod($periodStart, $periodEnd);

            $timeEntries = $this->getEligibleTimeEntries(
                $payroll,
                $periodStart,
                $periodEnd,
            );

            $rate = (string) $employee->hourly_rate;
            $multiplier = (string) $payroll->overtime_multiplier;

            $regularMinutes = 0;
            $overtimeMinutes = 0;
            $regularAmountCents = 0;
            $overtimeAmountCents = 0;

            foreach ($timeEntries as $timeEntry) {
                if ($timeEntry->entry_type === TimeEntryType::REGULAR) {
                    $regularMinutes += $timeEntry->working_minutes;

                    $regularAmountCents += $this->calculateRegularAmountCents(
                        $timeEntry->working_minutes,
                        $rate,
                    );

                    continue;
                }

                if ($timeEntry->entry_type === TimeEntryType::OVERTIME) {
                    $overtimeMinutes += $timeEntry->working_minutes;

                    $overtimeAmountCents += $this->calculateOvertimeAmountCents(
                        $timeEntry->working_minutes,
                        $rate,
                        $multiplier,
                    );
                }
            }

            $adjustmentCents = $this->moneyToCents(
                (string) $payroll->adjustment_amount
            );

            $deductionCents = $this->moneyToCents(
                (string) $payroll->deduction_amount
            );

            $grossCents =
                $regularAmountCents
                + $overtimeAmountCents;

            $netCents =
                $grossCents
                + $adjustmentCents
                - $deductionCents;

            $payroll->update([
                // Keep draft payroll rate synchronized with current rate.
                'hourly_rate' => $rate,

                'regular_minutes' => $regularMinutes,
                'overtime_minutes' => $overtimeMinutes,

                'regular_amount' => $this->centsToMoney(
                    $regularAmountCents
                ),

                'overtime_amount' => $this->centsToMoney(
                    $overtimeAmountCents
                ),

                'gross_amount' => $this->centsToMoney(
                    $grossCents
                ),

                'net_amount' => $this->centsToMoney(
                    $netCents
                ),
            ]);

            return $payroll->fresh([
                'employee',
                'timeEntries',
            ]);
        });
    }

    /**
     * Finalize payroll and permanently snapshot its calculation.
     */
    public function finalize(
        Payroll $payroll,
        User $user,
    ): Payroll {
        return DB::transaction(function () use (
            $payroll,
            $user,
        ): Payroll {
            $payroll = Payroll::query()
                ->lockForUpdate()
                ->findOrFail($payroll->id);

            $this->ensureDraft($payroll);

            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($payroll->employee_id);

            $this->ensureValidEmployee($employee);

            $periodStart = Carbon::parse($payroll->period_start);
            $periodEnd = Carbon::parse($payroll->period_end);

            $this->ensureValidPeriod($periodStart, $periodEnd);

            $timeEntries = $this->getEligibleTimeEntries(
                $payroll,
                $periodStart,
                $periodEnd,
            );

            if ($timeEntries->isEmpty()) {
                throw ValidationException::withMessages([
                    'payroll' => 'Payroll cannot be finalized because there are no approved time entries in this period.',
                ]);
            }

            /*
             * IMPORTANT:
             *
             * The employee's rate is captured at finalization.
             * Any later rate change cannot affect this payroll.
             */
            $rate = (string) $employee->hourly_rate;

            $multiplier = (string) $payroll->overtime_multiplier;

            $regularMinutes = 0;
            $overtimeMinutes = 0;

            $regularAmountCents = 0;
            $overtimeAmountCents = 0;

            /*
             * Remove only draft payroll lines.
             *
             * Normally a newly created payroll has no lines.
             * This also makes finalize idempotent before the status
             * changes to finalized.
             */
            $payroll->timeEntries()->delete();

            foreach ($timeEntries as $timeEntry) {
                $amountCents = match ($timeEntry->entry_type) {
                    TimeEntryType::REGULAR => $this->calculateRegularAmountCents(
                        $timeEntry->working_minutes,
                        $rate,
                    ),

                    TimeEntryType::OVERTIME => $this->calculateOvertimeAmountCents(
                        $timeEntry->working_minutes,
                        $rate,
                        $multiplier,
                    ),
                };

                if ($timeEntry->entry_type === TimeEntryType::REGULAR) {
                    $regularMinutes += $timeEntry->working_minutes;
                    $regularAmountCents += $amountCents;
                } else {
                    $overtimeMinutes += $timeEntry->working_minutes;
                    $overtimeAmountCents += $amountCents;
                }

                PayrollTimeEntry::create([
                    'payroll_id' => $payroll->id,
                    'time_entry_id' => $timeEntry->id,

                    // Historical snapshot.
                    'working_minutes' => $timeEntry->working_minutes,
                    'entry_type' => $timeEntry->entry_type,

                    // Historical rate snapshot.
                    'hourly_rate' => $rate,

                    // Historical calculated amount.
                    'amount' => $this->centsToMoney($amountCents),
                ]);
            }

            $adjustmentCents = $this->moneyToCents(
                (string) $payroll->adjustment_amount
            );

            $deductionCents = $this->moneyToCents(
                (string) $payroll->deduction_amount
            );

            $grossCents =
                $regularAmountCents
                + $overtimeAmountCents;

            $netCents =
                $grossCents
                + $adjustmentCents
                - $deductionCents;

            $payroll->update([
                'hourly_rate' => $rate,

                'regular_minutes' => $regularMinutes,
                'overtime_minutes' => $overtimeMinutes,

                'regular_amount' => $this->centsToMoney(
                    $regularAmountCents
                ),

                'overtime_amount' => $this->centsToMoney(
                    $overtimeAmountCents
                ),

                'gross_amount' => $this->centsToMoney(
                    $grossCents
                ),

                'net_amount' => $this->centsToMoney(
                    $netCents
                ),

                'status' => PayrollStatus::FINALIZED,

                'finalized_at' => now(),
                'finalized_by' => $user->id,
            ]);

            return $payroll->fresh([
                'employee',
                'finalizedBy',
                'timeEntries.timeEntry',
            ]);
        });
    }

    /**
     * Return approved entries eligible for the payroll.
     */
    private function getEligibleTimeEntries(
        Payroll $payroll,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): Collection {
        $query = TimeEntry::query()
            ->where('employee_id', $payroll->employee_id)
            ->whereBetween('work_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->where(
                'status',
                TimeEntryStatus::APPROVED,
            )
            ->whereIn(
                'entry_type',
                [
                    TimeEntryType::REGULAR,
                    TimeEntryType::OVERTIME,
                ],
            )
            ->orderBy('work_date')
            ->orderBy('start_time')
            ->lockForUpdate();

        $timeEntries = $query->get();

        $alreadyIncluded = TimeEntry::query()
            ->whereIn('id', $timeEntries->pluck('id'))
            ->whereHas('payrollEntries', function ($query) use ($payroll): void {
                $query->whereHas('payroll', function ($query) use ($payroll): void {
                    $query->where('id', '!=', $payroll->id)
                        ->where('status', PayrollStatus::FINALIZED);
                });
            })
            ->exists();

        if ($alreadyIncluded) {
            throw ValidationException::withMessages([
                'payroll' => 'One or more approved time entries are already included in another finalized payroll.',
            ]);
        }

        return $timeEntries;
    }

    /**
     * Ensure the employee can have payroll generated.
     */
    private function ensureValidEmployee(Employee $employee): void
    {
        if ($employee->status !== EmployeeStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'employee_id' => 'Payroll cannot be generated for an inactive employee.',
            ]);
        }
    }

    /**
     * Validate payroll period.
     */
    private function ensureValidPeriod(
        Carbon $periodStart,
        Carbon $periodEnd,
    ): void {
        if ($periodStart->greaterThan($periodEnd)) {
            throw ValidationException::withMessages([
                'period_start' => 'Payroll period start date must be before or equal to the end date.',
            ]);
        }
    }

    /**
     * Prevent duplicate or overlapping payroll periods.
     */
    private function ensureNoDuplicatePayroll(
        Employee $employee,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): void {
        $exists = Payroll::query()
            ->where('employee_id', $employee->id)
            ->where(function ($query) use (
                $periodStart,
                $periodEnd,
            ): void {
                $query
                    ->whereDate('period_start', '<=', $periodEnd->toDateString())
                    ->whereDate('period_end', '>=', $periodStart->toDateString());
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'payroll' => 'A payroll already exists for this employee and overlaps the selected payroll period.',
            ]);
        }
    }

    /**
     * Ensure payroll is still editable/calculable.
     */
    private function ensureDraft(Payroll $payroll): void
    {
        if ($payroll->status !== PayrollStatus::DRAFT) {
            throw ValidationException::withMessages([
                'payroll' => 'Finalized payroll cannot be modified or recalculated.',
            ]);
        }
    }

    /**
     * Validate overtime multiplier.
     */
    private function validateOvertimeMultiplier(string $multiplier): void
    {
        $value = (float) $multiplier;

        if ($value <= 0 || $value > 10) {
            throw ValidationException::withMessages([
                'overtime_multiplier' => 'Overtime multiplier must be greater than 0 and no more than 10.',
            ]);
        }
    }

    /**
     * Adjustment can be positive or negative.
     */
    private function validateAdjustmentAmount(string $amount): void
    {
        $this->moneyToCents($amount);
    }

    /**
     * Deductions cannot be negative.
     */
    private function validateDeductionAmount(string $amount): void
    {
        $cents = $this->moneyToCents($amount);

        if ($cents < 0) {
            throw ValidationException::withMessages([
                'deduction_amount' => 'Deduction amount cannot be negative.',
            ]);
        }
    }

    /**
     * Calculate regular pay in cents.
     */
    private function calculateRegularAmountCents(
        int $workingMinutes,
        string $hourlyRate,
    ): int {
        $rateCents = $this->moneyToCents($hourlyRate);

        return (int) round(
            ($workingMinutes * $rateCents) / 60
        );
    }

    /**
     * Calculate overtime pay in cents.
     */
    private function calculateOvertimeAmountCents(
        int $workingMinutes,
        string $hourlyRate,
        string $multiplier,
    ): int {
        $rateCents = $this->moneyToCents($hourlyRate);
        $multiplierHundredths = $this->decimalToHundredths($multiplier);

        return (int) round(
            (
                $workingMinutes
                * $rateCents
                * $multiplierHundredths
            ) / (60 * 100)
        );
    }

    /**
     * Convert decimal money into integer cents.
     *
     * Examples:
     * 750       -> 75000
     * 750.50    -> 75050
     * -25.25    -> -2525
     */
    private function moneyToCents(string $amount): int
    {
        $amount = trim($amount);

        if (!preg_match(
            '/^-?\d+(?:\.\d{1,2})?$/',
            $amount
        )) {
            throw ValidationException::withMessages([
                'amount' => 'Money amounts must contain a valid value with no more than two decimal places.',
            ]);
        }

        $negative = str_starts_with($amount, '-');

        if ($negative) {
            $amount = substr($amount, 1);
        }

        [$whole, $decimal] = array_pad(
            explode('.', $amount, 2),
            2,
            '0',
        );

        $decimal = str_pad(
            $decimal,
            2,
            '0',
        );

        $cents =
            ((int) $whole * 100)
            + (int) $decimal;

        return $negative ? -$cents : $cents;
    }

    /**
     * Convert integer cents into a database-ready decimal string.
     */
    private function centsToMoney(int $cents): string
    {
        $negative = $cents < 0;
        $cents = abs($cents);

        $whole = intdiv($cents, 100);
        $decimal = $cents % 100;

        $value = sprintf(
            '%d.%02d',
            $whole,
            $decimal,
        );

        return $negative ? "-{$value}" : $value;
    }

    /**
     * Convert a decimal multiplier such as 1.50 into 150.
     */
    private function decimalToHundredths(string $value): int
    {
        $value = trim($value);

        if (!preg_match(
            '/^\d+(?:\.\d{1,2})?$/',
            $value
        )) {
            throw ValidationException::withMessages([
                'overtime_multiplier' => 'Overtime multiplier must contain a valid value with no more than two decimal places.',
            ]);
        }

        [$whole, $decimal] = array_pad(
            explode('.', $value, 2),
            2,
            '0',
        );

        $decimal = str_pad(
            $decimal,
            2,
            '0',
        );

        return ((int) $whole * 100) + (int) $decimal;
    }

    /**
     * Normalize a date input.
     */
    private function parseDate(
        Carbon|string $date,
    ): Carbon {
        return $date instanceof Carbon
            ? $date->copy()->startOfDay()
            : Carbon::parse($date)->startOfDay();
    }
}