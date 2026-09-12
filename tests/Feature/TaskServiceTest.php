<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('project_manager', 'web');
    Role::findOrCreate('employee', 'web');

    $this->service = app(TaskService::class);

    $this->department = Department::factory()->create();
    $this->designation = Designation::factory()->create();
});

/*
|--------------------------------------------------------------------------
| Test Helpers
|--------------------------------------------------------------------------
*/

function createTaskUser(
    string $role,
    string $name = 'Test User',
): array {
    $user = User::factory()->create([
        'name' => $name,
    ]);

    $user->assignRole($role);

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => test()->department->id,
        'designation_id' => test()->designation->id,
        'status' => 'active',
    ]);

    return [$user, $employee];
}

function createManagedProject(Employee $manager): Project
{
    return Project::factory()->active()->create([
        'project_manager_id' => $manager->id,
    ]);
}

function addProjectMember(
    Project $project,
    Employee $employee,
): void {
    $project->members()->attach($employee->id, [
        'assigned_at' => now(),
    ]);
}

function taskData(
    Project $project,
    array $overrides = [],
): array {
    return array_merge([
        'project_id' => $project->id,
        'parent_task_id' => null,
        'title' => 'Build employee dashboard',
        'description' => 'Create the employee dashboard module.',
        'priority' => TaskPriority::MEDIUM,
        'status' => TaskStatus::TODO,
        'due_date' => now()->addDays(7)->format('Y-m-d'),
        'estimated_minutes' => 240,
        'assignees' => [],
    ], $overrides);
}

/*
|--------------------------------------------------------------------------
| Create
|--------------------------------------------------------------------------
*/

it('allows an admin to create a task', function (): void {
    [$admin, $adminEmployee] = createTaskUser(
        'admin',
        'System Admin'
    );

    $project = Project::factory()->active()->create([
        'project_manager_id' => $adminEmployee->id,
    ]);

    $task = $this->service->create(
        $admin,
        taskData($project)
    );

    expect($task)
        ->toBeInstanceOf(Task::class)
        ->project_id->toBe($project->id)
        ->title->toBe('Build employee dashboard');
});

it('allows a project manager to create a task only in their managed project', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    $managedProject = createManagedProject($managerEmployee);

    $task = $this->service->create(
        $manager,
        taskData($managedProject)
    );

    expect($task->project_id)->toBe($managedProject->id);
});

it('prevents a project manager from creating a task in another manager project', function (): void {
    [$manager] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    [, $otherManager] = createTaskUser(
        'project_manager',
        'Other Manager'
    );

    $otherProject = createManagedProject($otherManager);

    expect(fn () => $this->service->create(
        $manager,
        taskData($otherProject)
    ))->toThrow(ValidationException::class);

    expect(Task::query()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Assignees
|--------------------------------------------------------------------------
*/

it('requires every assignee to be an active member of the project', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    [, $employee] = createTaskUser(
        'employee',
        'Rahul Patil'
    );

    $project = createManagedProject($managerEmployee);

    expect(fn () => $this->service->create(
        $manager,
        taskData($project, [
            'assignees' => [$employee->id],
        ])
    ))->toThrow(ValidationException::class);

    expect(Task::query()->count())->toBe(0);
});

it('allows only active project members to be assigned', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    [, $employee] = createTaskUser(
        'employee',
        'Rahul Patil'
    );

    $project = createManagedProject($managerEmployee);

    addProjectMember($project, $employee);

    $task = $this->service->create(
        $manager,
        taskData($project, [
            'assignees' => [$employee->id],
        ])
    );

    expect($task->activeAssignees)
        ->toHaveCount(1);

    expect($task->activeAssignees->first()->id)
        ->toBe($employee->id);
});

/*
|--------------------------------------------------------------------------
| Parent Tasks
|--------------------------------------------------------------------------
*/

it('rejects a parent task from another project', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    $project = createManagedProject($managerEmployee);

    [, $otherManagerEmployee] = createTaskUser(
        'project_manager',
        'Other Manager'
    );

    $otherProject = createManagedProject($otherManagerEmployee);

    $parentTask = Task::factory()->create([
        'project_id' => $otherProject->id,
    ]);

    expect(fn () => $this->service->create(
        $manager,
        taskData($project, [
            'parent_task_id' => $parentTask->id,
        ])
    ))->toThrow(ValidationException::class);

    expect(Task::query()->count())->toBe(1);
});

it('prevents a task from being its own parent', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    $project = createManagedProject($managerEmployee);

    $task = Task::factory()->create([
        'project_id' => $project->id,
    ]);

    expect(fn () => $this->service->update(
        $manager,
        $task,
        taskData($project, [
            'parent_task_id' => $task->id,
        ])
    ))->toThrow(ValidationException::class);
});

/*
|--------------------------------------------------------------------------
| Assignment History
|--------------------------------------------------------------------------
*/

it('preserves task assignment history when an assignee is removed', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    [, $employeeOne] = createTaskUser(
        'employee',
        'Rahul Patil'
    );

    [, $employeeTwo] = createTaskUser(
        'employee',
        'Neha Kulkarni'
    );

    $project = createManagedProject($managerEmployee);

    addProjectMember($project, $employeeOne);
    addProjectMember($project, $employeeTwo);

    $task = $this->service->create(
        $manager,
        taskData($project, [
            'assignees' => [
                $employeeOne->id,
                $employeeTwo->id,
            ],
        ])
    );

    $this->service->update(
        $manager,
        $task,
        taskData($project, [
            'assignees' => [
                $employeeTwo->id,
            ],
        ])
    );

    $assignment = DB::table('task_assignees')
        ->where('task_id', $task->id)
        ->where('employee_id', $employeeOne->id)
        ->first();

    expect($assignment)
        ->not->toBeNull()
        ->removed_at->not->toBeNull();

    expect(
        DB::table('task_assignees')
            ->where('task_id', $task->id)
            ->where('employee_id', $employeeTwo->id)
            ->whereNull('removed_at')
            ->exists()
    )->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Task Status Rules
|--------------------------------------------------------------------------
*/

it('prevents modification of completed tasks', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    $project = createManagedProject($managerEmployee);

    $task = Task::factory()->completed()->create([
        'project_id' => $project->id,
    ]);

    expect(fn () => $this->service->update(
        $manager,
        $task,
        taskData($project, [
            'title' => 'Attempted modification',
        ])
    ))->toThrow(ValidationException::class);
});

it('prevents modification of cancelled tasks', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    $project = createManagedProject($managerEmployee);

    $task = Task::factory()->cancelled()->create([
        'project_id' => $project->id,
    ]);

    expect(fn () => $this->service->update(
        $manager,
        $task,
        taskData($project, [
            'title' => 'Attempted modification',
        ])
    ))->toThrow(ValidationException::class);
});

/*
|--------------------------------------------------------------------------
| Project Status Rules
|--------------------------------------------------------------------------
*/

it('prevents creating tasks in completed projects', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    $project = Project::factory()->completed()->create([
        'project_manager_id' => $managerEmployee->id,
    ]);

    expect(fn () => $this->service->create(
        $manager,
        taskData($project)
    ))->toThrow(ValidationException::class);

    expect(Task::query()->count())->toBe(0);
});

it('prevents creating tasks in cancelled projects', function (): void {
    [$manager, $managerEmployee] = createTaskUser(
        'project_manager',
        'Amit Sharma'
    );

    $project = Project::factory()->cancelled()->create([
        'project_manager_id' => $managerEmployee->id,
    ]);

    expect(fn () => $this->service->create(
        $manager,
        taskData($project)
    ))->toThrow(ValidationException::class);

    expect(Task::query()->count())->toBe(0);
});