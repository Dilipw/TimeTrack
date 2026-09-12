<?php

declare(strict_types=1);

use App\Enums\EmployeeStatus;
use App\Enums\ProjectStatus;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\ProjectMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('project_manager', 'web');
    Role::findOrCreate('employee', 'web');
});

function createProjectMemberUser(
    string $name = 'Test User',
    string $email = 'test@example.com',
): array {
    $department = Department::factory()->create();
    $designation = Designation::factory()->create();

    $user = User::factory()->create([
        'name' => $name,
        'email' => $email,
    ]);

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'status' => EmployeeStatus::ACTIVE,
    ]);

    return [$user, $employee];
}

function createProjectForMemberTest(Employee $manager): Project
{
    return Project::factory()->create([
        'project_manager_id' => $manager->id,
        'status' => ProjectStatus::ACTIVE,
    ]);
}

it('allows an admin to add an active employee to a project', function () {
    [$admin] = createProjectMemberUser('Admin', 'admin@example.com');
    $admin->assignRole('admin');

    [, $manager] = createProjectMemberUser('Manager', 'manager@example.com');

    [, $employee] = createProjectMemberUser('Employee', 'employee@example.com');

    $project = createProjectForMemberTest($manager);

    $membership = app(ProjectMemberService::class)->add(
        $admin,
        $project,
        $employee->id,
    );

    expect($membership->employee_id)->toBe($employee->id)
        ->and($membership->project_id)->toBe($project->id)
        ->and($membership->removed_at)->toBeNull();
});

it('allows a project manager to manage members of their own project', function () {
    [$managerUser, $manager] = createProjectMemberUser(
        'Manager',
        'manager@example.com',
    );

    $managerUser->assignRole('project_manager');

    [, $employee] = createProjectMemberUser(
        'Employee',
        'employee@example.com',
    );

    $project = createProjectForMemberTest($manager);

    $membership = app(ProjectMemberService::class)->add(
        $managerUser,
        $project,
        $employee->id,
    );

    expect($membership->employee_id)->toBe($employee->id);
});

it('prevents a project manager from managing another manager project', function () {
    [$managerUser, $manager] = createProjectMemberUser(
        'Manager',
        'manager@example.com',
    );

    $managerUser->assignRole('project_manager');

    [, $otherManager] = createProjectMemberUser(
        'Other Manager',
        'other-manager@example.com',
    );

    [, $employee] = createProjectMemberUser(
        'Employee',
        'employee@example.com',
    );

    $project = createProjectForMemberTest($otherManager);

    expect(fn () => app(ProjectMemberService::class)->add(
        $managerUser,
        $project,
        $employee->id,
    ))->toThrow(ValidationException::class);
});

it('prevents inactive employees from being added', function () {
    [$admin] = createProjectMemberUser(
        'Admin',
        'admin@example.com',
    );

    $admin->assignRole('admin');

    [, $manager] = createProjectMemberUser(
        'Manager',
        'manager@example.com',
    );

    [, $employee] = createProjectMemberUser(
        'Inactive Employee',
        'inactive@example.com',
    );

    $employee->update([
        'status' => EmployeeStatus::INACTIVE,
    ]);

    $project = createProjectForMemberTest($manager);

    expect(fn () => app(ProjectMemberService::class)->add(
        $admin,
        $project,
        $employee->id,
    ))->toThrow(ValidationException::class);
});

it('prevents duplicate active membership', function () {
    [$admin] = createProjectMemberUser(
        'Admin',
        'admin@example.com',
    );

    $admin->assignRole('admin');

    [, $manager] = createProjectMemberUser(
        'Manager',
        'manager@example.com',
    );

    [, $employee] = createProjectMemberUser(
        'Employee',
        'employee@example.com',
    );

    $project = createProjectForMemberTest($manager);

    $service = app(ProjectMemberService::class);

    $service->add($admin, $project, $employee->id);

    expect(fn () => $service->add(
        $admin,
        $project,
        $employee->id,
    ))->toThrow(ValidationException::class);
});

it('removes a member without deleting membership history', function () {
    [$admin] = createProjectMemberUser(
        'Admin',
        'admin@example.com',
    );

    $admin->assignRole('admin');

    [, $manager] = createProjectMemberUser(
        'Manager',
        'manager@example.com',
    );

    [, $employee] = createProjectMemberUser(
        'Employee',
        'employee@example.com',
    );

    $project = createProjectForMemberTest($manager);

    $service = app(ProjectMemberService::class);

    $membership = $service->add(
        $admin,
        $project,
        $employee->id,
    );

    $removed = $service->remove(
        $admin,
        $project,
        $employee->id,
    );

    expect($removed->id)->toBe($membership->id)
        ->and($removed->removed_at)->not->toBeNull();

    expect(ProjectMember::query()->count())->toBe(1);
});

it('reactivates an existing membership instead of creating a duplicate row', function () {
    [$admin] = createProjectMemberUser(
        'Admin',
        'admin@example.com',
    );

    $admin->assignRole('admin');

    [, $manager] = createProjectMemberUser(
        'Manager',
        'manager@example.com',
    );

    [, $employee] = createProjectMemberUser(
        'Employee',
        'employee@example.com',
    );

    $project = createProjectForMemberTest($manager);

    $service = app(ProjectMemberService::class);

    $membership = $service->add(
        $admin,
        $project,
        $employee->id,
    );

    $service->remove(
        $admin,
        $project,
        $employee->id,
    );

    $reactivated = $service->add(
        $admin,
        $project,
        $employee->id,
    );

    expect($reactivated->id)->toBe($membership->id)
        ->and($reactivated->removed_at)->toBeNull();

    expect(ProjectMember::query()->count())->toBe(1);
});

it('prevents member changes on completed projects', function () {
    [$admin] = createProjectMemberUser(
        'Admin',
        'admin@example.com',
    );

    $admin->assignRole('admin');

    [, $manager] = createProjectMemberUser(
        'Manager',
        'manager@example.com',
    );

    [, $employee] = createProjectMemberUser(
        'Employee',
        'employee@example.com',
    );

    $project = createProjectForMemberTest($manager);

    $project->update([
        'status' => ProjectStatus::COMPLETED,
    ]);

    expect(fn () => app(ProjectMemberService::class)->add(
        $admin,
        $project,
        $employee->id,
    ))->toThrow(ValidationException::class);
});

it('prevents employees from managing project members', function () {
    [$employeeUser, $employee] = createProjectMemberUser(
        'Employee',
        'employee@example.com',
    );

    $employeeUser->assignRole('employee');

    [, $manager] = createProjectMemberUser(
        'Manager',
        'manager@example.com',
    );

    [, $member] = createProjectMemberUser(
        'Member',
        'member@example.com',
    );

    $project = createProjectForMemberTest($manager);

    expect(fn () => app(ProjectMemberService::class)->add(
        $employeeUser,
        $project,
        $member->id,
    ))->toThrow(ValidationException::class);
});