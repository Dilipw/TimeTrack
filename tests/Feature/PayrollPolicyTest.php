<?php

declare(strict_types=1);

use App\Enums\PayrollStatus;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Policies\PayrollPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createPayrollPolicyContext(): array
{
    $department = Department::factory()->create();

    $designation = Designation::factory()->create();

    $admin = User::factory()->create([
        'name' => 'System Admin',
    ]);

    $managerUser = User::factory()->create([
        'name' => 'Project Manager',
    ]);

    $employeeUser = User::factory()->create([
        'name' => 'Employee',
    ]);

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('project_manager', 'web');
    Role::findOrCreate('employee', 'web');

    $admin->assignRole('super_admin');
    $managerUser->assignRole('project_manager');
    $employeeUser->assignRole('employee');

    $manager = Employee::factory()->create([
        'user_id' => $managerUser->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'hourly_rate' => 750,
    ]);

    $employee = Employee::factory()->create([
        'user_id' => $employeeUser->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'manager_id' => $manager->id,
        'hourly_rate' => 500,
    ]);

    $payroll = Payroll::factory()
        ->forEmployee($employee)
        ->create();

    return compact(
        'admin',
        'managerUser',
        'employeeUser',
        'manager',
        'employee',
        'payroll',
    );
}

test('admin can view payrolls', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->viewAny(
            $context['admin']
        )
    )->toBeTrue();

    expect(
        (new PayrollPolicy)->view(
            $context['admin'],
            $context['payroll']
        )
    )->toBeTrue();
});

test('admin can create payroll', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->create(
            $context['admin']
        )
    )->toBeTrue();
});

test('admin can update a draft payroll', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->update(
            $context['admin'],
            $context['payroll']
        )
    )->toBeTrue();
});

test('admin cannot update a finalized payroll', function () {
    $context = createPayrollPolicyContext();

    $context['payroll']->update([
        'status' => PayrollStatus::FINALIZED,
        'finalized_at' => now(),
        'finalized_by' => $context['admin']->id,
    ]);

    $context['payroll']->refresh();

    expect(
        (new PayrollPolicy)->update(
            $context['admin'],
            $context['payroll']
        )
    )->toBeFalse();
});

test('admin can delete a draft payroll', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->delete(
            $context['admin'],
            $context['payroll']
        )
    )->toBeTrue();
});

test('admin cannot delete a finalized payroll', function () {
    $context = createPayrollPolicyContext();

    $context['payroll']->update([
        'status' => PayrollStatus::FINALIZED,
        'finalized_at' => now(),
        'finalized_by' => $context['admin']->id,
    ]);

    $context['payroll']->refresh();

    expect(
        (new PayrollPolicy)->delete(
            $context['admin'],
            $context['payroll']
        )
    )->toBeFalse();
});

test('admin can restore a payroll', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->restore(
            $context['admin'],
            $context['payroll']
        )
    )->toBeTrue();
});

test('admin can force delete a draft payroll', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->forceDelete(
            $context['admin'],
            $context['payroll']
        )
    )->toBeTrue();
});

test('admin cannot force delete a finalized payroll', function () {
    $context = createPayrollPolicyContext();

    $context['payroll']->update([
        'status' => PayrollStatus::FINALIZED,
        'finalized_at' => now(),
        'finalized_by' => $context['admin']->id,
    ]);

    $context['payroll']->refresh();

    expect(
        (new PayrollPolicy)->forceDelete(
            $context['admin'],
            $context['payroll']
        )
    )->toBeFalse();
});

test('project manager cannot view payrolls', function () {
    $context = createPayrollPolicyContext();

    $policy = new PayrollPolicy;

    expect($policy->viewAny($context['managerUser']))->toBeFalse();

    expect(
        $policy->view(
            $context['managerUser'],
            $context['payroll']
        )
    )->toBeFalse();
});

test('employee cannot view payrolls', function () {
    $context = createPayrollPolicyContext();

    $policy = new PayrollPolicy;

    expect($policy->viewAny($context['employeeUser']))->toBeFalse();

    expect(
        $policy->view(
            $context['employeeUser'],
            $context['payroll']
        )
    )->toBeFalse();
});

test('project manager cannot create payroll', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->create(
            $context['managerUser']
        )
    )->toBeFalse();
});

test('employee cannot create payroll', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->create(
            $context['employeeUser']
        )
    )->toBeFalse();
});

test('project manager cannot update payroll', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->update(
            $context['managerUser'],
            $context['payroll']
        )
    )->toBeFalse();
});

test('employee cannot update payroll', function () {
    $context = createPayrollPolicyContext();

    expect(
        (new PayrollPolicy)->update(
            $context['employeeUser'],
            $context['payroll']
        )
    )->toBeFalse();
});
