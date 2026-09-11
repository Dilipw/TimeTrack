<?php

use App\Enums\EmployeeStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TimeEntryStatus;
use App\Enums\TimeEntryType;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TimeEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createTimeEntryContext(): array
{
    $user = User::factory()->create();

    $department = Department::factory()->create();

    $designation = Designation::factory()->create();

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'joining_date' => '2026-09-01',
        'status' => EmployeeStatus::ACTIVE,
    ]);

    $project = Project::factory()->create([
        'project_manager_id' => $employee->id,
        'start_date' => '2026-09-01',
        'end_date' => null,
        'status' => ProjectStatus::ACTIVE,
    ]);

    $project->members()->attach($employee->id, [
        'assigned_at' => now(),
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'priority' => TaskPriority::MEDIUM,
        'status' => TaskStatus::IN_PROGRESS,
    ]);

    $task->assignees()->attach($employee->id, [
        'assigned_at' => now(),
    ]);

    return compact(
        'user',
        'employee',
        'project',
        'task'
    );
}

function validTimeEntryData(): array
{
    return [
        'work_date' => '2026-09-10',
        'start_time' => '09:00',
        'end_time' => '18:00',
        'break_minutes' => 60,
        'entry_type' => TimeEntryType::REGULAR,
    ];
}

test('it creates a valid draft time entry', function () {
    $context = createTimeEntryContext();

    $service = app(TimeEntryService::class);

    $entry = $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        validTimeEntryData()
    );

    expect($entry)
        ->status->toBe(TimeEntryStatus::DRAFT)
        ->employee_id->toBe($context['employee']->id)
        ->project_id->toBe($context['project']->id)
        ->task_id->toBe($context['task']->id)
        ->working_minutes->toBe(480);

    expect($entry->exists)->toBeTrue();
});

test('it calculates working minutes on the server', function () {
    $context = createTimeEntryContext();

    $service = app(TimeEntryService::class);

    $entry = $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            'work_date' => '2026-09-10',
            'start_time' => '09:30',
            'end_time' => '17:30',
            'break_minutes' => 30,
            'entry_type' => TimeEntryType::REGULAR,
            'working_minutes' => 9999,
        ]
    );

    expect($entry->working_minutes)->toBe(450);
});

test('it rejects overlapping time entries', function () {
    $context = createTimeEntryContext();

    $service = app(TimeEntryService::class);

    $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            'work_date' => '2026-09-10',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'break_minutes' => 15,
            'entry_type' => TimeEntryType::REGULAR,
        ]
    );

    expect(fn() => $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            'work_date' => '2026-09-10',
            'start_time' => '11:00',
            'end_time' => '14:00',
            'break_minutes' => 15,
            'entry_type' => TimeEntryType::REGULAR,
        ]
    ))->toThrow(ValidationException::class);
});

test('it allows time entries that only touch at the boundary', function () {
    $context = createTimeEntryContext();

    $service = app(TimeEntryService::class);

    $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            'work_date' => '2026-09-10',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'break_minutes' => 15,
            'entry_type' => TimeEntryType::REGULAR,
        ]
    );

    $secondEntry = $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            'work_date' => '2026-09-10',
            'start_time' => '12:00',
            'end_time' => '14:00',
            'break_minutes' => 15,
            'entry_type' => TimeEntryType::REGULAR,
        ]
    );

    expect($secondEntry->exists)->toBeTrue();
});

test('it rejects time entry for an inactive employee', function () {
    $context = createTimeEntryContext();

    $context['employee']->update([
        'status' => EmployeeStatus::INACTIVE,
    ]);

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee']->fresh(),
        $context['project'],
        $context['task'],
        validTimeEntryData()
    ))->toThrow(ValidationException::class);
});

test('it rejects time entry when employee is not an active project member', function () {
    $context = createTimeEntryContext();

    $context['project']->members()->updateExistingPivot(
        $context['employee']->id,
        [
            'removed_at' => now(),
        ]
    );

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project']->fresh(),
        $context['task'],
        validTimeEntryData()
    ))->toThrow(ValidationException::class);
});

test('it rejects time entry when employee is not an active task assignee', function () {
    $context = createTimeEntryContext();

    $context['task']->assignees()->updateExistingPivot(
        $context['employee']->id,
        [
            'removed_at' => now(),
        ]
    );

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project'],
        $context['task']->fresh(),
        validTimeEntryData()
    ))->toThrow(ValidationException::class);
});

test('it rejects a task belonging to another project', function () {
    $context = createTimeEntryContext();

    $otherProject = Project::factory()->create([
        'project_manager_id' => $context['employee']->id,
        'start_date' => '2026-09-01',
        'status' => ProjectStatus::ACTIVE,
    ]);

    $otherProject->members()->attach(
        $context['employee']->id,
        ['assigned_at' => now()]
    );

    $otherTask = Task::factory()->create([
        'project_id' => $otherProject->id,
        'status' => TaskStatus::IN_PROGRESS,
    ]);

    $otherTask->assignees()->attach(
        $context['employee']->id,
        ['assigned_at' => now()]
    );

    expect(fn() => app(TimeEntryService::class)->create(
        $context['employee'],
        $context['project'],
        $otherTask,
        validTimeEntryData()
    ))->toThrow(ValidationException::class);
});

test('it rejects completed tasks', function () {
    $context = createTimeEntryContext();

    $context['task']->update([
        'status' => TaskStatus::COMPLETED,
    ]);

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project'],
        $context['task']->fresh(),
        validTimeEntryData()
    ))->toThrow(ValidationException::class);
});

test('it rejects cancelled tasks', function () {
    $context = createTimeEntryContext();

    $context['task']->update([
        'status' => TaskStatus::CANCELLED,
    ]);

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project'],
        $context['task']->fresh(),
        validTimeEntryData()
    ))->toThrow(ValidationException::class);
});

test('it rejects a non-active project', function () {
    $context = createTimeEntryContext();

    $context['project']->update([
        'status' => ProjectStatus::COMPLETED,
    ]);

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project']->fresh(),
        $context['task'],
        validTimeEntryData()
    ))->toThrow(ValidationException::class);
});

test('it rejects future work dates', function () {
    $context = createTimeEntryContext();

    $futureDate = now()
        ->addDay()
        ->format('Y-m-d');

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            ...validTimeEntryData(),
            'work_date' => $futureDate,
        ]
    ))->toThrow(ValidationException::class);
});

test('it rejects work dates before employee joining date', function () {
    $context = createTimeEntryContext();

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            ...validTimeEntryData(),
            'work_date' => '2026-08-31',
        ]
    ))->toThrow(ValidationException::class);
});

test('it rejects negative break duration', function () {
    $context = createTimeEntryContext();

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            ...validTimeEntryData(),
            'break_minutes' => -1,
        ]
    ))->toThrow(ValidationException::class);
});

test('it rejects break duration equal to total duration', function () {
    $context = createTimeEntryContext();

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            ...validTimeEntryData(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'break_minutes' => 60,
        ]
    ))->toThrow(ValidationException::class);
});

test('it rejects end time before start time', function () {
    $context = createTimeEntryContext();

    $service = app(TimeEntryService::class);

    expect(fn() => $service->create(
        $context['employee'],
        $context['project'],
        $context['task'],
        [
            ...validTimeEntryData(),
            'start_time' => '18:00',
            'end_time' => '09:00',
        ]
    ))->toThrow(ValidationException::class);
});
