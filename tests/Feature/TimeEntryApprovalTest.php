<?php

use App\Enums\ApprovalAction;
use App\Enums\EmployeeStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\TimeEntryStatus;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\TimesheetApproval;
use App\Models\User;
use App\Services\TimeEntryApprovalService;
use App\Services\TimeEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createApprovalContext(): array
{
    $employeeUser = User::factory()->create();

    $approverUser = User::factory()->create();

    $department = Department::factory()->create();
    $designation = Designation::factory()->create();

    $employee = Employee::factory()->create([
        'user_id' => $employeeUser->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'joining_date' => '2026-09-01',
        'status' => EmployeeStatus::ACTIVE,
    ]);

    $project = Project::factory()->create([
        'project_manager_id' => $employee->id,
        'start_date' => '2026-09-01',
        'status' => ProjectStatus::ACTIVE,
    ]);

    $project->members()->attach(
        $employee->id,
        ['assigned_at' => now()]
    );

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::IN_PROGRESS,
    ]);

    $task->assignees()->attach(
        $employee->id,
        ['assigned_at' => now()]
    );

    $timeEntry = TimeEntry::factory()->create([
        'employee_id' => $employee->id,
        'project_id' => $project->id,
        'task_id' => $task->id,
        'work_date' => '2026-09-10',
        'status' => TimeEntryStatus::SUBMITTED,
        'submitted_at' => now(),
    ]);

    return compact(
        'employeeUser',
        'approverUser',
        'employee',
        'project',
        'task',
        'timeEntry'
    );
}

test('it approves a submitted time entry', function () {
    $context = createApprovalContext();

    $approved = app(TimeEntryApprovalService::class)->approve(
        $context['timeEntry'],
        $context['approverUser']
    );

    expect($approved->status)
        ->toBe(TimeEntryStatus::APPROVED);

    expect($approved->approved_at)
        ->not->toBeNull();

    expect($approved->rejected_at)
        ->toBeNull();

    expect($approved->rejection_reason)
        ->toBeNull();
});

test('it creates approval history when approving', function () {
    $context = createApprovalContext();

    app(TimeEntryApprovalService::class)->approve(
        $context['timeEntry'],
        $context['approverUser']
    );

    $approval = TimesheetApproval::query()->first();

    expect($approval)->not->toBeNull()
        ->and($approval->time_entry_id)->toBe($context['timeEntry']->id)
        ->and($approval->approver_user_id)->toBe($context['approverUser']->id)
        ->and($approval->action)->toBe(ApprovalAction::APPROVED)
        ->and($approval->rejection_reason)->toBeNull()
        ->and($approval->acted_at)->not->toBeNull();
});

test('it rejects a submitted time entry', function () {
    $context = createApprovalContext();

    $rejected = app(TimeEntryApprovalService::class)->reject(
        $context['timeEntry'],
        $context['approverUser'],
        'Please correct the recorded working hours.'
    );

    expect($rejected->status)
        ->toBe(TimeEntryStatus::REJECTED);

    expect($rejected->rejected_at)
        ->not->toBeNull();

    expect($rejected->approved_at)
        ->toBeNull();

    expect($rejected->rejection_reason)
        ->toBe('Please correct the recorded working hours.');
});

test('it creates rejection history when rejecting', function () {
    $context = createApprovalContext();

    $reason = 'Please correct the recorded working hours.';

    app(TimeEntryApprovalService::class)->reject(
        $context['timeEntry'],
        $context['approverUser'],
        $reason
    );

    $approval = TimesheetApproval::query()->first();

    expect($approval)->not->toBeNull()
        ->and($approval->time_entry_id)->toBe($context['timeEntry']->id)
        ->and($approval->approver_user_id)->toBe($context['approverUser']->id)
        ->and($approval->action)->toBe(ApprovalAction::REJECTED)
        ->and($approval->rejection_reason)->toBe($reason)
        ->and($approval->acted_at)->not->toBeNull();
});

test('it requires a rejection reason', function () {
    $context = createApprovalContext();

    expect(fn () => app(TimeEntryApprovalService::class)->reject(
        $context['timeEntry'],
        $context['approverUser'],
        ''
    ))->toThrow(ValidationException::class);
});

test('it rejects whitespace-only rejection reasons', function () {
    $context = createApprovalContext();

    expect(fn () => app(TimeEntryApprovalService::class)->reject(
        $context['timeEntry'],
        $context['approverUser'],
        '   '
    ))->toThrow(ValidationException::class);
});

test('it cannot approve a draft time entry', function () {
    $context = createApprovalContext();

    $context['timeEntry']->update([
        'status' => TimeEntryStatus::DRAFT,
    ]);

    expect(fn () => app(TimeEntryApprovalService::class)->approve(
        $context['timeEntry'],
        $context['approverUser']
    ))->toThrow(ValidationException::class);
});

test('it cannot reject a draft time entry', function () {
    $context = createApprovalContext();

    $context['timeEntry']->update([
        'status' => TimeEntryStatus::DRAFT,
    ]);

    expect(fn () => app(TimeEntryApprovalService::class)->reject(
        $context['timeEntry'],
        $context['approverUser'],
        'Invalid entry.'
    ))->toThrow(ValidationException::class);
});

test('it cannot approve an already approved time entry', function () {
    $context = createApprovalContext();

    $context['timeEntry']->update([
        'status' => TimeEntryStatus::APPROVED,
        'approved_at' => now(),
    ]);

    expect(fn () => app(TimeEntryApprovalService::class)->approve(
        $context['timeEntry'],
        $context['approverUser']
    ))->toThrow(ValidationException::class);
});

test('it cannot approve a rejected time entry directly', function () {
    $context = createApprovalContext();

    $context['timeEntry']->update([
        'status' => TimeEntryStatus::REJECTED,
        'rejected_at' => now(),
        'rejection_reason' => 'Needs correction.',
    ]);

    expect(fn () => app(TimeEntryApprovalService::class)->approve(
        $context['timeEntry'],
        $context['approverUser']
    ))->toThrow(ValidationException::class);
});

test('an employee cannot approve their own time entry', function () {
    $context = createApprovalContext();

    expect(fn () => app(TimeEntryApprovalService::class)->approve(
        $context['timeEntry'],
        $context['employeeUser']
    ))->toThrow(ValidationException::class);
});

test('an employee cannot reject their own time entry', function () {
    $context = createApprovalContext();

    expect(fn () => app(TimeEntryApprovalService::class)->reject(
        $context['timeEntry'],
        $context['employeeUser'],
        'Please correct this entry.'
    ))->toThrow(ValidationException::class);
});

test('a rejected time entry can be resubmitted after correction', function () {
    $context = createApprovalContext();

    app(TimeEntryApprovalService::class)->reject(
        $context['timeEntry'],
        $context['approverUser'],
        'Please correct the recorded working hours.'
    );

    $context['timeEntry']->refresh();

    $resubmitted = app(TimeEntryService::class)->submit(
        $context['timeEntry'],
        $context['employee']
    );

    expect($resubmitted->status)
        ->toBe(TimeEntryStatus::SUBMITTED);

    expect($resubmitted->rejected_at)
        ->toBeNull();

    expect($resubmitted->rejection_reason)
        ->toBeNull();
});