<?php

namespace Database\Seeders;

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
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Departments
         */
        $engineering = Department::create([
            'name' => 'Engineering',
            'description' => 'Software development and technical delivery.',
        ]);

        $operations = Department::create([
            'name' => 'Operations',
            'description' => 'Business operations and project coordination.',
        ]);

        $finance = Department::create([
            'name' => 'Finance',
            'description' => 'Payroll, billing and financial operations.',
        ]);

        /*
         * Designations
         */
        $seniorDeveloper = Designation::create([
            'name' => 'Senior PHP Developer',
            'description' => 'Senior backend and Laravel development.',
        ]);

        $developer = Designation::create([
            'name' => 'PHP Developer',
            'description' => 'Application development and maintenance.',
        ]);

        $projectManager = Designation::create([
            'name' => 'Project Manager',
            'description' => 'Project planning, delivery and team coordination.',
        ]);

        $teamLead = Designation::create([
            'name' => 'Technical Lead',
            'description' => 'Technical leadership and code quality.',
        ]);

        $accountant = Designation::create([
            'name' => 'Accountant',
            'description' => 'Payroll and financial operations.',
        ]);

        /*
         * Users
         */
        $adminUser = User::create([
            'name' => 'System Admin',
            'email' => 'admin@timetrack.test',
            'password' => Hash::make('password'),
        ]);

        $pmUser = User::create([
            'name' => 'Amit Sharma',
            'email' => 'amit.pm@timetrack.test',
            'password' => Hash::make('password'),
        ]);

        $leadUser = User::create([
            'name' => 'Priya Deshmukh',
            'email' => 'priya.lead@timetrack.test',
            'password' => Hash::make('password'),
        ]);

        $employeeOneUser = User::create([
            'name' => 'Rahul Patil',
            'email' => 'rahul@timetrack.test',
            'password' => Hash::make('password'),
        ]);

        $employeeTwoUser = User::create([
            'name' => 'Neha Kulkarni',
            'email' => 'neha@timetrack.test',
            'password' => Hash::make('password'),
        ]);
        /*
 * Roles
 */
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $projectManagerRole = Role::findOrCreate('project_manager', 'web');
        $employeeRole = Role::findOrCreate('employee', 'web');

        $adminUser->assignRole($superAdminRole);

        $pmUser->assignRole($projectManagerRole);
        $leadUser->assignRole($projectManagerRole);

        $employeeOneUser->assignRole($employeeRole);
        $employeeTwoUser->assignRole($employeeRole);
        /*
         * Employees
         */
        $pm = Employee::create([
            'employee_code' => 'EMP001',
            'user_id' => $pmUser->id,
            'first_name' => 'Amit',
            'last_name' => 'Sharma',
            'department_id' => $operations->id,
            'designation_id' => $projectManager->id,
            'manager_id' => null,
            'joining_date' => '2024-01-15',
            'hourly_rate' => 750,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $lead = Employee::create([
            'employee_code' => 'EMP002',
            'user_id' => $leadUser->id,
            'first_name' => 'Priya',
            'last_name' => 'Deshmukh',
            'department_id' => $engineering->id,
            'designation_id' => $teamLead->id,
            'manager_id' => $pm->id,
            'joining_date' => '2023-06-01',
            'hourly_rate' => 900,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $employeeOne = Employee::create([
            'employee_code' => 'EMP003',
            'user_id' => $employeeOneUser->id,
            'first_name' => 'Rahul',
            'last_name' => 'Patil',
            'department_id' => $engineering->id,
            'designation_id' => $developer->id,
            'manager_id' => $lead->id,
            'joining_date' => '2025-02-10',
            'hourly_rate' => 500,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $employeeTwo = Employee::create([
            'employee_code' => 'EMP004',
            'user_id' => $employeeTwoUser->id,
            'first_name' => 'Neha',
            'last_name' => 'Kulkarni',
            'department_id' => $engineering->id,
            'designation_id' => $seniorDeveloper->id,
            'manager_id' => $lead->id,
            'joining_date' => '2024-08-12',
            'hourly_rate' => 650,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        /*
         * Projects
         */
        $activeProject = Project::create([
            'project_code' => 'PRJ-001',
            'name' => 'Client Portal',
            'description' => 'Customer-facing project management portal.',
            'project_manager_id' => $pm->id,
            'start_date' => '2026-08-01',
            'end_date' => null,
            'status' => ProjectStatus::ACTIVE,
            'budget' => 350000,
        ]);

        $planningProject = Project::create([
            'project_code' => 'PRJ-002',
            'name' => 'Internal HR Platform',
            'description' => 'Internal employee and HR management platform.',
            'project_manager_id' => $lead->id,
            'start_date' => '2026-10-01',
            'end_date' => null,
            'status' => ProjectStatus::PLANNING,
            'budget' => 200000,
        ]);

        $completedProject = Project::create([
            'project_code' => 'PRJ-003',
            'name' => 'Inventory Migration',
            'description' => 'Migration of legacy inventory data.',
            'project_manager_id' => $pm->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => ProjectStatus::COMPLETED,
            'budget' => 150000,
        ]);

        /*
         * Project memberships
         */
        $activeProject->members()->attach([
            $pm->id => ['assigned_at' => '2026-08-01 09:00:00'],
            $lead->id => ['assigned_at' => '2026-08-01 09:00:00'],
            $employeeOne->id => ['assigned_at' => '2026-08-01 09:00:00'],
            $employeeTwo->id => ['assigned_at' => '2026-08-05 09:00:00'],
        ]);

        /*
         * Tasks
         */
        $portalTask = Task::create([
            'project_id' => $activeProject->id,
            'parent_task_id' => null,
            'title' => 'Develop Authentication Module',
            'description' => 'Implement login, logout and role-based authentication.',
            'priority' => TaskPriority::HIGH,
            'status' => TaskStatus::IN_PROGRESS,
            'due_date' => '2026-09-20',
            'estimated_minutes' => 2400,
        ]);

        $apiTask = Task::create([
            'project_id' => $activeProject->id,
            'parent_task_id' => null,
            'title' => 'Develop Project API',
            'description' => 'Implement project and task management APIs.',
            'priority' => TaskPriority::URGENT,
            'status' => TaskStatus::TODO,
            'due_date' => '2026-09-25',
            'estimated_minutes' => 3000,
        ]);

        $loginTask = Task::create([
            'project_id' => $activeProject->id,
            'parent_task_id' => $portalTask->id,
            'title' => 'Implement Login',
            'description' => 'Implement secure user login.',
            'priority' => TaskPriority::HIGH,
            'status' => TaskStatus::COMPLETED,
            'due_date' => '2026-09-10',
            'estimated_minutes' => 900,
        ]);

        /*
         * Task assignments
         */
        $portalTask->assignees()->attach([
            $lead->id => ['assigned_at' => '2026-08-01 09:00:00'],
            $employeeOne->id => ['assigned_at' => '2026-08-01 09:00:00'],
        ]);

        $apiTask->assignees()->attach([
            $employeeOne->id => ['assigned_at' => '2026-08-10 09:00:00'],
            $employeeTwo->id => ['assigned_at' => '2026-08-10 09:00:00'],
        ]);

        $loginTask->assignees()->attach([
            $employeeOne->id => ['assigned_at' => '2026-08-01 09:00:00'],
        ]);

        /*
         * Time entries
         *
         * These are seeded directly because the service is intentionally
         * responsible for validating user-created time entries.
         */
        TimeEntry::create([
            'employee_id' => $employeeOne->id,
            'project_id' => $activeProject->id,
            'task_id' => $portalTask->id,
            'work_date' => '2026-09-08',
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'break_minutes' => 0,
            'working_minutes' => 240,
            'entry_type' => TimeEntryType::REGULAR,
            'status' => TimeEntryStatus::APPROVED,
            'submitted_at' => '2026-09-08 18:00:00',
            'approved_at' => '2026-09-09 10:00:00',
        ]);

        TimeEntry::create([
            'employee_id' => $employeeOne->id,
            'project_id' => $activeProject->id,
            'task_id' => $apiTask->id,
            'work_date' => '2026-09-09',
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'break_minutes' => 0,
            'working_minutes' => 180,
            'entry_type' => TimeEntryType::OVERTIME,
            'status' => TimeEntryStatus::APPROVED,
            'submitted_at' => '2026-09-09 21:30:00',
            'approved_at' => '2026-09-10 10:00:00',
        ]);

        TimeEntry::create([
            'employee_id' => $employeeTwo->id,
            'project_id' => $activeProject->id,
            'task_id' => $apiTask->id,
            'work_date' => '2026-09-10',
            'start_time' => '09:30:00',
            'end_time' => '17:30:00',
            'break_minutes' => 60,
            'working_minutes' => 420,
            'entry_type' => TimeEntryType::REGULAR,
            'status' => TimeEntryStatus::SUBMITTED,
            'submitted_at' => '2026-09-10 18:00:00',
        ]);

        TimeEntry::create([
            'employee_id' => $employeeOne->id,
            'project_id' => $activeProject->id,
            'task_id' => $portalTask->id,
            'work_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'break_minutes' => 0,
            'working_minutes' => 180,
            'entry_type' => TimeEntryType::REGULAR,
            'status' => TimeEntryStatus::REJECTED,
            'submitted_at' => '2026-09-10 18:00:00',
            'rejected_at' => '2026-09-11 09:00:00',
            'rejection_reason' => 'Please provide more details about the work completed.',
        ]);

        TimeEntry::create([
            'employee_id' => $employeeTwo->id,
            'project_id' => $activeProject->id,
            'task_id' => $apiTask->id,
            'work_date' => '2026-09-11',
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'break_minutes' => 0,
            'working_minutes' => 180,
            'entry_type' => TimeEntryType::REGULAR,
            'status' => TimeEntryStatus::DRAFT,
        ]);

        /*
         * Keep the admin user available for authentication.
         *
         * Role assignment will be handled after Shield permissions
         * and roles are generated.
         */
        $this->command?->info('Demo data seeded successfully.');
    }
}
