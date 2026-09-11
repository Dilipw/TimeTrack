<?php

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
use App\Models\User;
use App\Policies\TimeEntryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createPolicyContext(): array
{

    Role::findOrCreate('project_manager', 'web');
    Role::findOrCreate('employee', 'web');

    $employeeUser = User::factory()->create();

    $managerUser = User::factory()->create();

    $otherManagerUser = User::factory()->create();

    $department = Department::factory()->create();

    $designation = Designation::factory()->create();

    $employee = Employee::factory()->create([
        'user_id' => $employeeUser->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'joining_date' => '2026-09-01',
        'status' => EmployeeStatus::ACTIVE,
    ]);

    $manager = Employee::factory()->create([
        'user_id' => $managerUser->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'joining_date' => '2026-09-01',
        'status' => EmployeeStatus::ACTIVE,
    ]);

    $otherManager = Employee::factory()->create([
        'user_id' => $otherManagerUser->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'joining_date' => '2026-09-01',
        'status' => EmployeeStatus::ACTIVE,
    ]);
    $employeeUser->assignRole('employee');
    $managerUser->assignRole('project_manager');
    $otherManagerUser->assignRole('project_manager');
    $project = Project::factory()->create([
        'project_manager_id' => $manager->id,
        'start_date' => '2026-09-01',
        'status' => ProjectStatus::ACTIVE,
    ]);

    $project->members()->attach([
        $manager->id => ['assigned_at' => now()],
        $employee->id => ['assigned_at' => now()],
    ]);

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
        'status' => TimeEntryStatus::SUBMITTED,
        'submitted_at' => now(),
    ]);

    return compact(
        'employeeUser',
        'managerUser',
        'otherManagerUser',
        'employee',
        'manager',
        'otherManager',
        'project',
        'task',
        'timeEntry'
    );
}

test('employee can view their own time entry', function () {
    $context = createPolicyContext();

    expect(
        (new TimeEntryPolicy)->view(
            $context['employeeUser'],
            $context['timeEntry']
        )
    )->toBeTrue();
});

test('project manager can view time entry from their project', function () {
    $context = createPolicyContext();

    expect(
        (new TimeEntryPolicy)->view(
            $context['managerUser'],
            $context['timeEntry']
        )
    )->toBeTrue();
});

test('unrelated project manager cannot view the time entry', function () {
    $context = createPolicyContext();

    expect(
        (new TimeEntryPolicy)->view(
            $context['otherManagerUser'],
            $context['timeEntry']
        )
    )->toBeFalse();
});

test('active employee can create time entries', function () {
    $context = createPolicyContext();

    expect(
        (new TimeEntryPolicy)->create(
            $context['employeeUser']
        )
    )->toBeTrue();
});

test('inactive employee cannot create time entries', function () {
    $context = createPolicyContext();

    $context['employee']->update([
        'status' => EmployeeStatus::INACTIVE,
    ]);

    expect(
        (new TimeEntryPolicy)->create(
            $context['employeeUser']
        )
    )->toBeFalse();
});

test('employee can update their own draft entry', function () {
    $context = createPolicyContext();

    $context['timeEntry']->update([
        'status' => TimeEntryStatus::DRAFT,
    ]);

    expect(
        (new TimeEntryPolicy)->update(
            $context['employeeUser'],
            $context['timeEntry']
        )
    )->toBeTrue();
});

test('employee cannot update their submitted entry', function () {
    $context = createPolicyContext();

    expect(
        (new TimeEntryPolicy)->update(
            $context['employeeUser'],
            $context['timeEntry']
        )
    )->toBeFalse();
});

test('employee can update their rejected entry', function () {
    $context = createPolicyContext();

    $context['timeEntry']->update([
        'status' => TimeEntryStatus::REJECTED,
    ]);

    expect(
        (new TimeEntryPolicy)->update(
            $context['employeeUser'],
            $context['timeEntry']
        )
    )->toBeTrue();
});

test('project manager can approve a submitted entry from their project', function () {
    $context = createPolicyContext();

    $context['managerUser']->assignRole('project_manager');

    expect(
        (new TimeEntryPolicy)->approve(
            $context['managerUser'],
            $context['timeEntry']
        )
    )->toBeTrue();
});

test('project manager cannot approve an entry from another project', function () {
    $context = createPolicyContext();

    $otherProject = Project::factory()->create([
        'project_manager_id' => $context['otherManager']->id,
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

    $otherEntry = TimeEntry::factory()->create([
        'employee_id' => $context['employee']->id,
        'project_id' => $otherProject->id,
        'task_id' => $otherTask->id,
        'status' => TimeEntryStatus::SUBMITTED,
        'submitted_at' => now(),
    ]);

    expect(
        (new TimeEntryPolicy)->approve(
            $context['managerUser'],
            $otherEntry
        )
    )->toBeFalse();
});

test('employee cannot approve their own time entry', function () {
    $context = createPolicyContext();

    expect(
        (new TimeEntryPolicy)->approve(
            $context['employeeUser'],
            $context['timeEntry']
        )
    )->toBeFalse();
});

test('project manager can reject a submitted entry from their project', function () {
    $context = createPolicyContext();

    expect(
        (new TimeEntryPolicy)->reject(
            $context['managerUser'],
            $context['timeEntry']
        )
    )->toBeTrue();
});

test('project manager cannot approve a non-submitted entry', function () {
    $context = createPolicyContext();

    $context['timeEntry']->update([
        'status' => TimeEntryStatus::DRAFT,
    ]);

    expect(
        (new TimeEntryPolicy)->approve(
            $context['managerUser'],
            $context['timeEntry']
        )
    )->toBeFalse();
});
