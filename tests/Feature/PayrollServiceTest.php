<?php

declare(strict_types=1);

use App\Enums\EmployeeStatus;
use App\Enums\PayrollStatus;
use App\Enums\TimeEntryStatus;
use App\Enums\TimeEntryType;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollTimeEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createPayrollEmployee(
    string $hourlyRate = '600.00'
): Employee {
    $user = User::factory()->create();

    return Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => Department::factory()->create()->id,
        'designation_id' => Designation::factory()->create()->id,
        'hourly_rate' => $hourlyRate,
        'status' => EmployeeStatus::ACTIVE,
    ]);
}

function createPayrollProjectFor(Employee $employee): Project {
    $project = Project::factory()->create([
        'project_manager_id' => $employee->id,
        'status' => 'active',
        'start_date' => '2026-09-01',
        'end_date' => null,
    ]);

    $project->members()->attach($employee->id);

    return $project;
}

function createPayrollTask(
    Project $project,
    Employee $employee
): Task {
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in_progress',
    ]);

    $task->assignees()->attach($employee->id);

    return $task;
}

function createApprovedPayrollEntry(
    Employee $employee,
    Project $project,
    Task $task,
    string $date,
    int $workingMinutes,
    TimeEntryType $type = TimeEntryType::REGULAR,
): TimeEntry {
    return TimeEntry::factory()->create([
        'employee_id' => $employee->id,
        'project_id' => $project->id,
        'task_id' => $task->id,
        'work_date' => $date,
        'start_time' => '09:00:00',
        'end_time' => '18:00:00',
        'break_minutes' => 0,
        'working_minutes' => $workingMinutes,
        'entry_type' => $type,
        'status' => TimeEntryStatus::APPROVED,
        'submitted_at' => now(),
        'approved_at' => now(),
    ]);
}

test('it creates a draft payroll for an employee', function () {
    $employee = createPayrollEmployee('750.00');

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    expect($payroll)
        ->toBeInstanceOf(Payroll::class)
        ->and($payroll->employee_id)->toBe($employee->id)
        ->and($payroll->status)->toBe(PayrollStatus::DRAFT)
        ->and((string) $payroll->hourly_rate)->toBe('750.00')
        ->and($payroll->period_start->toDateString())->toBe('2026-09-01')
        ->and($payroll->period_end->toDateString())->toBe('2026-09-30');
});

test('it calculates approved regular hours correctly', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        240,
        TimeEntryType::REGULAR,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $payroll = app(PayrollService::class)->calculate($payroll);

    expect($payroll->regular_minutes)->toBe(240)
        ->and((string) $payroll->regular_amount)->toBe('2400.00')
        ->and((string) $payroll->gross_amount)->toBe('2400.00')
        ->and((string) $payroll->net_amount)->toBe('2400.00');
});

test('it calculates approved overtime using the overtime multiplier', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        120,
        TimeEntryType::OVERTIME,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
        [
            'overtime_multiplier' => '1.50',
        ],
    );

    $payroll = app(PayrollService::class)->calculate($payroll);

    expect($payroll->overtime_minutes)->toBe(120)
        ->and((string) $payroll->overtime_amount)->toBe('1800.00')
        ->and((string) $payroll->gross_amount)->toBe('1800.00');
});

test('it calculates regular and overtime together', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        240,
        TimeEntryType::REGULAR,
    );

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-11',
        120,
        TimeEntryType::OVERTIME,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $payroll = app(PayrollService::class)->calculate($payroll);

    expect($payroll->regular_minutes)->toBe(240)
        ->and($payroll->overtime_minutes)->toBe(120)
        ->and((string) $payroll->regular_amount)->toBe('2400.00')
        ->and((string) $payroll->overtime_amount)->toBe('1800.00')
        ->and((string) $payroll->gross_amount)->toBe('4200.00')
        ->and((string) $payroll->net_amount)->toBe('4200.00');
});

test('it excludes non approved time entries', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    foreach ([
        TimeEntryStatus::DRAFT,
        TimeEntryStatus::SUBMITTED,
        TimeEntryStatus::REJECTED,
        TimeEntryStatus::CANCELLED,
    ] as $status) {
        TimeEntry::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'task_id' => $task->id,
            'work_date' => '2026-09-10',
            'working_minutes' => 240,
            'entry_type' => TimeEntryType::REGULAR,
            'status' => $status,
        ]);
    }

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $payroll = app(PayrollService::class)->calculate($payroll);

    expect($payroll->regular_minutes)->toBe(0)
        ->and($payroll->overtime_minutes)->toBe(0)
        ->and((string) $payroll->gross_amount)->toBe('0.00');
});

test('it excludes approved time entries outside the payroll period', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-08-31',
        240,
    );

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-10-01',
        240,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $payroll = app(PayrollService::class)->calculate($payroll);

    expect($payroll->regular_minutes)->toBe(0)
        ->and((string) $payroll->gross_amount)->toBe('0.00');
});

test('it applies deductions and adjustments correctly', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        480,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
        [
            'adjustment_amount' => '500.00',
            'deduction_amount' => '1000.00',
        ],
    );

    $payroll = app(PayrollService::class)->calculate($payroll);

    expect((string) $payroll->regular_amount)->toBe('4800.00')
        ->and((string) $payroll->gross_amount)->toBe('4800.00')
        ->and((string) $payroll->adjustment_amount)->toBe('500.00')
        ->and((string) $payroll->deduction_amount)->toBe('1000.00')
        ->and((string) $payroll->net_amount)->toBe('4300.00');
});

test('it supports negative adjustments', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        480,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
        [
            'adjustment_amount' => '-300.00',
        ],
    );

    $payroll = app(PayrollService::class)->calculate($payroll);

    expect((string) $payroll->gross_amount)->toBe('4800.00')
        ->and((string) $payroll->net_amount)->toBe('4500.00');
});

test('it cannot create payroll for an inactive employee', function () {
    $employee = createPayrollEmployee('600.00');

    $employee->update([
        'status' => EmployeeStatus::INACTIVE,
    ]);

    expect(fn () => app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    ))->toThrow(ValidationException::class);
});

test('it rejects an invalid payroll period', function () {
    $employee = createPayrollEmployee();

    expect(fn () => app(PayrollService::class)->create(
        $employee,
        '2026-09-30',
        '2026-09-01',
    ))->toThrow(ValidationException::class);
});

test('it prevents overlapping payroll periods for the same employee', function () {
    $employee = createPayrollEmployee();

    $service = app(PayrollService::class);

    $service->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    expect(fn () => $service->create(
        $employee,
        '2026-09-15',
        '2026-10-15',
    ))->toThrow(ValidationException::class);
});

test('it allows payroll periods for different employees', function () {
    $employeeOne = createPayrollEmployee();
    $employeeTwo = createPayrollEmployee();

    $service = app(PayrollService::class);

    $payrollOne = $service->create(
        $employeeOne,
        '2026-09-01',
        '2026-09-30',
    );

    $payrollTwo = $service->create(
        $employeeTwo,
        '2026-09-01',
        '2026-09-30',
    );

    expect($payrollOne->employee_id)
        ->toBe($employeeOne->id)
        ->and($payrollTwo->employee_id)
        ->toBe($employeeTwo->id);
});

test('it cannot finalize payroll without approved time entries', function () {
    $employee = createPayrollEmployee();

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $admin = User::factory()->create();

    expect(fn () => app(PayrollService::class)->finalize(
        $payroll,
        $admin,
    ))->toThrow(ValidationException::class);

    expect($payroll->fresh()->status)
        ->toBe(PayrollStatus::DRAFT);
});

test('it finalizes payroll and creates historical time entry snapshots', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    $timeEntry = createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        240,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $admin = User::factory()->create();

    $finalized = app(PayrollService::class)->finalize(
        $payroll,
        $admin,
    );

    expect($finalized->status)
        ->toBe(PayrollStatus::FINALIZED)
        ->and($finalized->finalized_by)
        ->toBe($admin->id)
        ->and($finalized->finalized_at)
        ->not->toBeNull();

    $line = PayrollTimeEntry::query()
        ->where('payroll_id', $payroll->id)
        ->where('time_entry_id', $timeEntry->id)
        ->first();

    expect($line)
        ->not->toBeNull()
        ->and($line->working_minutes)
        ->toBe(240)
        ->and((string) $line->hourly_rate)
        ->toBe('600.00')
        ->and((string) $line->amount)
        ->toBe('2400.00');
});

test('it snapshots the employee hourly rate when payroll is finalized', function () {
    $employee = createPayrollEmployee('500.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        120,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $employee->update([
        'hourly_rate' => 800.00,
    ]);

    $admin = User::factory()->create();

    $finalized = app(PayrollService::class)->finalize(
        $payroll,
        $admin,
    );

    expect((string) $finalized->hourly_rate)
        ->toBe('800.00')
        ->and((string) $finalized->regular_amount)
        ->toBe('1600.00');
});

test('a finalized payroll remains unchanged after employee rate changes', function () {
    $employee = createPayrollEmployee('500.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        120,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $admin = User::factory()->create();

    $finalized = app(PayrollService::class)->finalize(
        $payroll,
        $admin,
    );

    $employee->update([
        'hourly_rate' => 1000.00,
    ]);

    $freshPayroll = $finalized->fresh();

    expect((string) $freshPayroll->hourly_rate)
        ->toBe('500.00')
        ->and((string) $freshPayroll->regular_amount)
        ->toBe('1000.00')
        ->and((string) $freshPayroll->net_amount)
        ->toBe('1000.00');
});

test('it prevents recalculating finalized payroll', function () {
    $employee = createPayrollEmployee('500.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        120,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $admin = User::factory()->create();

    app(PayrollService::class)->finalize(
        $payroll,
        $admin,
    );

    expect(fn () => app(PayrollService::class)->calculate(
        $payroll->fresh(),
    ))->toThrow(ValidationException::class);
});

test('it prevents finalizing an already finalized payroll', function () {
    $employee = createPayrollEmployee('500.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        120,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $admin = User::factory()->create();

    app(PayrollService::class)->finalize(
        $payroll,
        $admin,
    );

    expect(fn () => app(PayrollService::class)->finalize(
        $payroll->fresh(),
        $admin,
    ))->toThrow(ValidationException::class);
});

test('it prevents an approved time entry from being included in another finalized payroll', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    $timeEntry = createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        240,
    );

    $firstPayroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-15',
    );

    $admin = User::factory()->create();

    app(PayrollService::class)->finalize(
        $firstPayroll,
        $admin,
    );

    // Directly create another draft payroll for this test.
    // The service's period-overlap protection is tested separately.
    $secondPayroll = Payroll::factory()->create([
        'employee_id' => $employee->id,
        'period_start' => '2026-09-16',
        'period_end' => '2026-09-30',
        'hourly_rate' => $employee->hourly_rate,
        'status' => PayrollStatus::DRAFT,
    ]);

    $timeEntry->update([
        'work_date' => '2026-09-20',
    ]);

    expect(fn () => app(PayrollService::class)->finalize(
        $secondPayroll,
        $admin,
    ))->toThrow(ValidationException::class);
});

test('it creates one payroll line per approved time entry', function () {
    $employee = createPayrollEmployee('600.00');

    $project = createPayrollProjectFor($employee);
    $task = createPayrollTask($project, $employee);

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-10',
        120,
        TimeEntryType::REGULAR,
    );

    createApprovedPayrollEntry(
        $employee,
        $project,
        $task,
        '2026-09-11',
        60,
        TimeEntryType::OVERTIME,
    );

    $payroll = app(PayrollService::class)->create(
        $employee,
        '2026-09-01',
        '2026-09-30',
    );

    $admin = User::factory()->create();

    app(PayrollService::class)->finalize(
        $payroll,
        $admin,
    );

    expect(
        PayrollTimeEntry::query()
            ->where('payroll_id', $payroll->id)
            ->count()
    )->toBe(2);
});