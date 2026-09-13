<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ApprovalAction;
use App\Enums\EmployeeStatus;
use App\Enums\PayrollStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TimeEntryStatus;
use App\Enums\TimeEntryType;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\TimesheetApproval;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\TimeEntryApprovalService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Creating demo data...');

        $departments = $this->createDepartments();
        $designations = $this->createDesignations();
        $roles = $this->createRoles();

        $admin = $this->createAdmin($roles['super_admin']);

        $employees = $this->createEmployees(
            departments: $departments,
            designations: $designations,
            roles: $roles,
        );

        $projects = $this->createProjects($employees);

        $this->createProjectMembers(
            projects: $projects,
            employees: $employees,
        );

        $tasks = $this->createTasks($projects);

        $this->createTaskAssignments(
            tasks: $tasks,
            projects: $projects,
        );

        $this->createFixedTimeEntries(
            projects: $projects,
            tasks: $tasks,
            employees: $employees,
        );

        $this->createBulkTimeEntries(
            projects: $projects,
            tasks: $tasks,
            employees: $employees,
        );

        $this->createApprovalHistory();

        $this->createPayrolls(
            admin: $admin,
        );

        $this->command?->info('');
        $this->command?->info('Demo data seeded successfully.');
        $this->command?->info('');
        $this->command?->info('Login credentials:');
        $this->command?->info('Admin: admin@timetrack.test / password');
        $this->command?->info('PM:    amit.pm@timetrack.test / password');
        $this->command?->info('PM:    priya.lead@timetrack.test / password');
        $this->command?->info('User:  rahul@timetrack.test / password');
        $this->command?->info('User:  neha@timetrack.test / password');
    }

    /*
    |--------------------------------------------------------------------------
    | Departments
    |--------------------------------------------------------------------------
    */

    private function createDepartments(): array
    {
        $definitions = [
            [
                'name' => 'Engineering',
                'description' => 'Software development and technical delivery.',
            ],
            [
                'name' => 'Operations',
                'description' => 'Business operations and project coordination.',
            ],
            [
                'name' => 'Finance',
                'description' => 'Payroll, billing and financial operations.',
            ],
            [
                'name' => 'Human Resources',
                'description' => 'People operations and employee management.',
            ],
            [
                'name' => 'Quality Assurance',
                'description' => 'Software testing and quality engineering.',
            ],
        ];

        $departments = [];

        foreach ($definitions as $definition) {
            $department = Department::create($definition);

            $departments[$department->name] = $department;
        }

        return $departments;
    }

    /*
    |--------------------------------------------------------------------------
    | Designations
    |--------------------------------------------------------------------------
    */

    private function createDesignations(): array
    {
        $definitions = [
            [
                'name' => 'Senior PHP Developer',
                'description' => 'Senior backend and Laravel development.',
            ],
            [
                'name' => 'PHP Developer',
                'description' => 'Application development and maintenance.',
            ],
            [
                'name' => 'Junior PHP Developer',
                'description' => 'Junior application development.',
            ],
            [
                'name' => 'Project Manager',
                'description' => 'Project planning, delivery and team coordination.',
            ],
            [
                'name' => 'Technical Lead',
                'description' => 'Technical leadership and code quality.',
            ],
            [
                'name' => 'QA Engineer',
                'description' => 'Application testing and quality assurance.',
            ],
            [
                'name' => 'HR Executive',
                'description' => 'Human resources operations.',
            ],
            [
                'name' => 'Accountant',
                'description' => 'Payroll and financial operations.',
            ],
        ];

        $designations = [];

        foreach ($definitions as $definition) {
            $designation = Designation::create($definition);

            $designations[$designation->name] = $designation;
        }

        return $designations;
    }

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */

    private function createRoles(): array
    {
        return [
            'super_admin' => Role::findOrCreate('super_admin', 'web'),
            'project_manager' => Role::findOrCreate('project_manager', 'web'),
            'employee' => Role::findOrCreate('employee', 'web'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    private function createAdmin(Role $role): User
    {
        $user = User::create([
            'name' => 'System Admin',
            'email' => 'admin@timetrack.test',
            'password' => Hash::make('password'),
        ]);

        $user->assignRole($role);

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | Employees
    |--------------------------------------------------------------------------
    */

    private function createEmployees(
        array $departments,
        array $designations,
        array $roles,
    ): array {
        $amit = $this->createEmployee(
            name: 'Amit Sharma',
            email: 'amit.pm@timetrack.test',
            code: 'EMP001',
            firstName: 'Amit',
            lastName: 'Sharma',
            department: $departments['Operations'],
            designation: $designations['Project Manager'],
            manager: null,
            joiningDate: '2024-01-15',
            hourlyRate: 750,
            role: $roles['project_manager'],
        );

        $priya = $this->createEmployee(
            name: 'Priya Deshmukh',
            email: 'priya.lead@timetrack.test',
            code: 'EMP002',
            firstName: 'Priya',
            lastName: 'Deshmukh',
            department: $departments['Engineering'],
            designation: $designations['Technical Lead'],
            manager: $amit,
            joiningDate: '2023-06-01',
            hourlyRate: 900,
            role: $roles['project_manager'],
        );

        $rahul = $this->createEmployee(
            name: 'Rahul Patil',
            email: 'rahul@timetrack.test',
            code: 'EMP003',
            firstName: 'Rahul',
            lastName: 'Patil',
            department: $departments['Engineering'],
            designation: $designations['PHP Developer'],
            manager: $priya,
            joiningDate: '2025-02-10',
            hourlyRate: 500,
            role: $roles['employee'],
        );

        $neha = $this->createEmployee(
            name: 'Neha Kulkarni',
            email: 'neha@timetrack.test',
            code: 'EMP004',
            firstName: 'Neha',
            lastName: 'Kulkarni',
            department: $departments['Engineering'],
            designation: $designations['Senior PHP Developer'],
            manager: $priya,
            joiningDate: '2024-08-12',
            hourlyRate: 650,
            role: $roles['employee'],
        );

        $employees = [
            'EMP001' => $amit,
            'EMP002' => $priya,
            'EMP003' => $rahul,
            'EMP004' => $neha,
        ];

        $profiles = [
            ['Aarav', 'Joshi', 'Engineering', 'Senior PHP Developer', 850],
            ['Sneha', 'Patil', 'Engineering', 'PHP Developer', 550],
            ['Rohan', 'Kulkarni', 'Engineering', 'PHP Developer', 600],
            ['Pooja', 'Shinde', 'Engineering', 'Junior PHP Developer', 400],
            ['Akash', 'Jadhav', 'Engineering', 'PHP Developer', 575],
            ['Kiran', 'Desai', 'Engineering', 'Senior PHP Developer', 820],
            ['Meera', 'Pawar', 'Engineering', 'PHP Developer', 525],
            ['Vivek', 'More', 'Engineering', 'Junior PHP Developer', 425],
            ['Anjali', 'Chavan', 'Quality Assurance', 'QA Engineer', 500],
            ['Sagar', 'Bhosale', 'Quality Assurance', 'QA Engineer', 525],
            ['Nikita', 'Gaikwad', 'Quality Assurance', 'QA Engineer', 550],
            ['Manish', 'Kale', 'Operations', 'PHP Developer', 575],
            ['Ritu', 'Thakur', 'Operations', 'PHP Developer', 600],
            ['Nilesh', 'Wagh', 'Operations', 'Junior PHP Developer', 425],
            ['Swati', 'Mane', 'Human Resources', 'HR Executive', 500],
            ['Deepak', 'Raut', 'Finance', 'Accountant', 650],
            ['Komal', 'Kadam', 'Finance', 'Accountant', 575],
            ['Varun', 'Pawar', 'Engineering', 'Technical Lead', 950],
            ['Isha', 'Suryawanshi', 'Engineering', 'Senior PHP Developer', 875],
            ['Omkar', 'Mhatre', 'Engineering', 'PHP Developer', 525],
        ];

        foreach ($profiles as $index => $profile) {
            [
                $firstName,
                $lastName,
                $departmentName,
                $designationName,
                $rate,
            ] = $profile;

            $number = $index + 5;

            $manager = $index % 2 === 0
                ? $priya
                : $amit;

            $employee = $this->createEmployee(
                name: "{$firstName} {$lastName}",
                email: strtolower("{$firstName}.{$lastName}") . '@timetrack.test',
                code: sprintf('EMP%03d', $number),
                firstName: $firstName,
                lastName: $lastName,
                department: $departments[$departmentName],
                designation: $designations[$designationName],
                manager: $manager,
                joiningDate: Carbon::create(2024, 1, 1)
                    ->addDays(($index * 17) % 500)
                    ->format('Y-m-d'),
                hourlyRate: $rate,
                role: $roles['employee'],
            );

            $employees[$employee->employee_code] = $employee;
        }

        /*
         * Inactive employee.
         */
        $inactive = $this->createEmployee(
            name: 'Inactive Employee',
            email: 'inactive@timetrack.test',
            code: 'EMP025',
            firstName: 'Inactive',
            lastName: 'Employee',
            department: $departments['Engineering'],
            designation: $designations['PHP Developer'],
            manager: $priya,
            joiningDate: '2023-01-10',
            hourlyRate: 450,
            role: $roles['employee'],
            status: EmployeeStatus::INACTIVE,
        );

        $employees[$inactive->employee_code] = $inactive;

        return $employees;
    }

    private function createEmployee(
        string $name,
        string $email,
        string $code,
        string $firstName,
        string $lastName,
        Department $department,
        Designation $designation,
        ?Employee $manager,
        string $joiningDate,
        int|float $hourlyRate,
        Role $role,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): Employee {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        $user->assignRole($role);

        return Employee::create([
            'employee_code' => $code,
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'manager_id' => $manager?->id,
            'joining_date' => $joiningDate,
            'hourly_rate' => $hourlyRate,
            'status' => $status,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */

    private function createProjects(array $employees): array
    {
        $definitions = [
            [
                'Client Portal',
                ProjectStatus::ACTIVE,
                350000,
                '2026-08-01',
                null,
                'EMP001',
            ],
            [
                'Internal HR Platform',
                ProjectStatus::PLANNING,
                200000,
                '2026-10-01',
                null,
                'EMP002',
            ],
            [
                'Inventory Migration',
                ProjectStatus::COMPLETED,
                150000,
                '2026-01-01',
                '2026-06-30',
                'EMP001',
            ],
            [
                'Mobile Banking API',
                ProjectStatus::ACTIVE,
                500000,
                '2026-07-01',
                null,
                'EMP002',
            ],
            [
                'E-Commerce Platform',
                ProjectStatus::ACTIVE,
                425000,
                '2026-06-15',
                null,
                'EMP001',
            ],
            [
                'Customer Support Portal',
                ProjectStatus::ACTIVE,
                275000,
                '2026-08-15',
                null,
                'EMP002',
            ],
            [
                'Legacy CRM Upgrade',
                ProjectStatus::COMPLETED,
                180000,
                '2026-02-01',
                '2026-07-31',
                'EMP001',
            ],
            [
                'Analytics Dashboard',
                ProjectStatus::PLANNING,
                220000,
                '2026-11-01',
                null,
                'EMP002',
            ],
            [
                'Vendor Management System',
                ProjectStatus::ACTIVE,
                310000,
                '2026-08-20',
                null,
                'EMP001',
            ],
            [
                'Warehouse Automation',
                ProjectStatus::CANCELLED,
                175000,
                '2026-03-01',
                '2026-05-15',
                'EMP002',
            ],
            [
                'Payment Reconciliation',
                ProjectStatus::ACTIVE,
                195000,
                '2026-09-01',
                null,
                'EMP001',
            ],
            [
                'Employee Self Service',
                ProjectStatus::ACTIVE,
                240000,
                '2026-08-10',
                null,
                'EMP002',
            ],
            [
                'Reporting Engine',
                ProjectStatus::COMPLETED,
                160000,
                '2026-01-15',
                '2026-05-31',
                'EMP001',
            ],
            [
                'Document Management',
                ProjectStatus::PLANNING,
                285000,
                '2026-12-01',
                null,
                'EMP002',
            ],
            [
                'Notification Service',
                ProjectStatus::ACTIVE,
                125000,
                '2026-09-05',
                null,
                'EMP001',
            ],
        ];

        $projects = [];

        foreach ($definitions as $index => $definition) {
            [
                $name,
                $status,
                $budget,
                $startDate,
                $endDate,
                $managerCode,
            ] = $definition;

            $project = Project::create([
                'project_code' => sprintf('PRJ-%03d', $index + 1),
                'name' => $name,
                'description' => "{$name} project for demo and workflow testing.",
                'project_manager_id' => $employees[$managerCode]->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'budget' => $budget,
            ]);

            $projects[$project->project_code] = $project;
        }

        return $projects;
    }

    /*
    |--------------------------------------------------------------------------
    | Project Members
    |--------------------------------------------------------------------------
    */

    private function createProjectMembers(
        array $projects,
        array $employees,
    ): void {
        $activeEmployees = collect($employees)
            ->filter(
                fn (Employee $employee): bool =>
                    $employee->status === EmployeeStatus::ACTIVE,
            )
            ->values();

        $projectList = array_values($projects);

        foreach ($projectList as $projectIndex => $project) {
            $manager = Employee::query()->findOrFail(
                $project->project_manager_id,
            );

            $others = $activeEmployees
                ->reject(
                    fn (Employee $employee): bool =>
                        $employee->id === $manager->id,
                )
                ->values();

            $take = min(
                7 + ($projectIndex % 5),
                $others->count(),
            );

            $selected = $others
                ->slice(
                    ($projectIndex * 3) % max(1, $others->count()),
                    $take,
                )
                ->values();

            $selected->prepend($manager);

            $assignedAt = Carbon::parse($project->start_date)
                ->setTime(9, 0);

            foreach ($selected->unique('id') as $employee) {
                $project->members()->syncWithoutDetaching([
                    $employee->id => [
                        'assigned_at' => $assignedAt,
                        'removed_at' => null,
                    ],
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Tasks
    |--------------------------------------------------------------------------
    */

    private function createTasks(array $projects): array
    {
        $titles = [
            'Authentication Module',
            'User Management',
            'Project Management',
            'Task Management',
            'Dashboard',
            'Reporting',
            'API Integration',
            'Database Optimization',
            'Notification System',
            'Email Integration',
            'File Upload',
            'Search Functionality',
            'Role Management',
            'Permission Management',
            'Audit Logging',
            'Performance Optimization',
            'Security Hardening',
            'Automated Testing',
            'Documentation',
            'Deployment Preparation',
        ];

        $priorities = [
            TaskPriority::LOW,
            TaskPriority::MEDIUM,
            TaskPriority::HIGH,
            TaskPriority::URGENT,
        ];

        $tasks = [];

        foreach (array_values($projects) as $projectIndex => $project) {
            $taskCount = 8 + ($projectIndex % 5);

            for ($index = 0; $index < $taskCount; $index++) {
                $status = $this->taskStatusForProject(
                    $project->status,
                    $index,
                );

                $dueDate = match ($status) {
                    TaskStatus::COMPLETED => Carbon::parse($project->start_date)
                        ->addDays(15 + $index)
                        ->format('Y-m-d'),

                    TaskStatus::CANCELLED => Carbon::parse($project->start_date)
                        ->addDays(10 + $index)
                        ->format('Y-m-d'),

                    default => $index % 4 === 0
                        ? now()->subDays(2)->format('Y-m-d')
                        : now()->addDays(5 + $index)->format('Y-m-d'),
                };

                $task = Task::create([
                    'project_id' => $project->id,
                    'parent_task_id' => null,
                    'title' => "{$titles[$index % count($titles)]} - {$project->name}",
                    'description' => 'Demo task for testing task assignment and time tracking.',
                    'priority' => $priorities[$index % count($priorities)],
                    'status' => $status,
                    'due_date' => $dueDate,
                    'estimated_minutes' => 240 + (($index % 6) * 120),
                ]);

                $tasks[] = $task;
            }
        }

        /*
         * Subtasks.
         */
        foreach (array_slice(array_values($projects), 0, 8) as $project) {
            $parent = collect($tasks)->first(
                fn (Task $task): bool =>
                    $task->project_id === $project->id
                    && $task->parent_task_id === null,
            );

            if (! $parent) {
                continue;
            }

            for ($index = 1; $index <= 2; $index++) {
                $tasks[] = Task::create([
                    'project_id' => $project->id,
                    'parent_task_id' => $parent->id,
                    'title' => "Subtask {$index} - {$parent->title}",
                    'description' => 'Child task for hierarchy testing.',
                    'priority' => $index === 1
                        ? TaskPriority::MEDIUM
                        : TaskPriority::HIGH,
                    'status' => $index === 1
                        ? TaskStatus::IN_PROGRESS
                        : TaskStatus::TODO,
                    'due_date' => now()
                        ->addDays(7 + $index)
                        ->format('Y-m-d'),
                    'estimated_minutes' => 180 + ($index * 60),
                ]);
            }
        }

        return $tasks;
    }

    private function taskStatusForProject(
        ProjectStatus $projectStatus,
        int $index,
    ): TaskStatus {
        return match ($projectStatus) {
            ProjectStatus::COMPLETED => TaskStatus::COMPLETED,
            ProjectStatus::CANCELLED => TaskStatus::CANCELLED,

            ProjectStatus::PLANNING => $index % 2 === 0
                ? TaskStatus::TODO
                : TaskStatus::IN_PROGRESS,

            ProjectStatus::ACTIVE => match ($index % 4) {
                0 => TaskStatus::TODO,
                1 => TaskStatus::IN_PROGRESS,
                2 => TaskStatus::COMPLETED,
                default => TaskStatus::IN_PROGRESS,
            },
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Task Assignments
    |--------------------------------------------------------------------------
    */

    private function createTaskAssignments(
        array $tasks,
        array $projects,
    ): void {
        foreach ($tasks as $taskIndex => $task) {
            $project = collect($projects)->firstWhere(
                'id',
                $task->project_id,
            );

            if (! $project) {
                continue;
            }

            $members = $project->members()
                ->wherePivotNull('removed_at')
                ->get()
                ->values();

            if ($members->isEmpty()) {
                continue;
            }

            $take = min(
                1 + ($taskIndex % 3),
                $members->count(),
            );

            $offset = $taskIndex % $members->count();

            $selected = $members
                ->concat($members)
                ->slice($offset, $take)
                ->unique('id')
                ->values();

            foreach ($selected as $employee) {
                $task->assignees()->syncWithoutDetaching([
                    $employee->id => [
                        'assigned_at' => Carbon::parse($project->start_date)
                            ->setTime(9, 0),
                        'removed_at' => null,
                    ],
                ]);
            }
        }

        /*
         * Explicit removed-assignment scenario.
         */
        $firstTask = $tasks[0] ?? null;

        if ($firstTask) {
            $member = $firstTask->activeAssignees()->first();

            if ($member) {
                $firstTask->assignees()->updateExistingPivot(
                    $member->id,
                    [
                        'removed_at' => now()->subDays(2),
                    ],
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fixed Time Entry Scenarios
    |--------------------------------------------------------------------------
    */

    private function createFixedTimeEntries(
        array $projects,
        array $tasks,
        array $employees,
    ): void {
        $project = $projects['PRJ-001'];

        $projectTasks = collect($tasks)
            ->where('project_id', $project->id)
            ->filter(
                fn (Task $task): bool =>
                    in_array(
                        $task->status,
                        [
                            TaskStatus::TODO,
                            TaskStatus::IN_PROGRESS,
                        ],
                        true,
                    ),
            )
            ->values();

        $regularTask = $projectTasks->first();
        $overtimeTask = $projectTasks->skip(1)->first();

        if (! $regularTask || ! $overtimeTask) {
            return;
        }

        /*
         * Rahul - approved regular.
         */
        $this->createTimeEntry(
            employee: $employees['EMP003'],
            project: $project,
            task: $regularTask,
            workDate: '2026-09-08',
            startTime: '09:00:00',
            endTime: '13:00:00',
            breakMinutes: 0,
            workingMinutes: 240,
            entryType: TimeEntryType::REGULAR,
            status: TimeEntryStatus::APPROVED,
            submittedAt: '2026-09-08 18:00:00',
            approvedAt: '2026-09-09 10:00:00',
        );

        /*
         * Rahul - approved overtime.
         */
        $this->createTimeEntry(
            employee: $employees['EMP003'],
            project: $project,
            task: $overtimeTask,
            workDate: '2026-09-09',
            startTime: '18:00:00',
            endTime: '21:00:00',
            breakMinutes: 0,
            workingMinutes: 180,
            entryType: TimeEntryType::OVERTIME,
            status: TimeEntryStatus::APPROVED,
            submittedAt: '2026-09-09 21:30:00',
            approvedAt: '2026-09-10 10:00:00',
        );

        /*
         * Neha - submitted.
         *
         * This one intentionally goes through the actual approval
         * service in createApprovalHistory().
         */
        $this->createTimeEntry(
            employee: $employees['EMP004'],
            project: $project,
            task: $overtimeTask,
            workDate: '2026-09-10',
            startTime: '09:30:00',
            endTime: '17:30:00',
            breakMinutes: 60,
            workingMinutes: 420,
            entryType: TimeEntryType::REGULAR,
            status: TimeEntryStatus::SUBMITTED,
            submittedAt: '2026-09-10 18:00:00',
        );

        /*
         * Rahul - rejected.
         */
        $this->createTimeEntry(
            employee: $employees['EMP003'],
            project: $project,
            task: $regularTask,
            workDate: '2026-09-10',
            startTime: '09:00:00',
            endTime: '12:00:00',
            breakMinutes: 0,
            workingMinutes: 180,
            entryType: TimeEntryType::REGULAR,
            status: TimeEntryStatus::REJECTED,
            submittedAt: '2026-09-10 18:00:00',
            rejectionReason: 'Please provide more details about the work completed.',
            rejectedAt: '2026-09-11 09:00:00',
        );

        /*
         * Neha - draft.
         */
        $this->createTimeEntry(
            employee: $employees['EMP004'],
            project: $project,
            task: $overtimeTask,
            workDate: '2026-09-11',
            startTime: '09:00:00',
            endTime: '12:00:00',
            breakMinutes: 0,
            workingMinutes: 180,
            entryType: TimeEntryType::REGULAR,
            status: TimeEntryStatus::DRAFT,
        );

        /*
         * Rahul - cancelled.
         */
        $this->createTimeEntry(
            employee: $employees['EMP003'],
            project: $project,
            task: $regularTask,
            workDate: '2026-09-06',
            startTime: '14:00:00',
            endTime: '16:00:00',
            breakMinutes: 0,
            workingMinutes: 120,
            entryType: TimeEntryType::REGULAR,
            status: TimeEntryStatus::CANCELLED,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Time Entries
    |--------------------------------------------------------------------------
    */

    private function createBulkTimeEntries(
        array $projects,
        array $tasks,
        array $employees,
    ): void {
        $activeEmployees = collect($employees)
            ->filter(
                fn (Employee $employee): bool =>
                    $employee->status === EmployeeStatus::ACTIVE,
            )
            ->values();

        $activeProjects = collect($projects)
            ->filter(
                fn (Project $project): bool =>
                    $project->status === ProjectStatus::ACTIVE,
            )
            ->values();

        $created = 0;

        /*
         * Every active employee gets 12 generated entries.
         *
         * Generated entries use only active projects and tasks that
         * are currently valid for time tracking.
         */
        foreach ($activeEmployees as $employeeIndex => $employee) {
            $employeeProjects = $activeProjects
                ->filter(
                    fn (Project $project): bool =>
                        $project->members()
                            ->where('employees.id', $employee->id)
                            ->wherePivotNull('removed_at')
                            ->exists(),
                )
                ->values();

            if ($employeeProjects->isEmpty()) {
                continue;
            }

            for ($entryIndex = 0; $entryIndex < 12; $entryIndex++) {
                $project = $employeeProjects[
                    ($employeeIndex + $entryIndex)
                    % $employeeProjects->count()
                ];

                /*
                 * Only TODO / IN_PROGRESS tasks are used for new
                 * time entries, matching TimeEntryService rules.
                 */
                $employeeTasks = collect($tasks)
                    ->filter(
                        function (Task $task) use ($project, $employee): bool {
                            return $task->project_id === $project->id
                                && in_array(
                                    $task->status,
                                    [
                                        TaskStatus::TODO,
                                        TaskStatus::IN_PROGRESS,
                                    ],
                                    true,
                                )
                                && $task->activeAssignees
                                    ->contains('id', $employee->id);
                        },
                    )
                    ->values();

                if ($employeeTasks->isEmpty()) {
                    continue;
                }

                $task = $employeeTasks[
                    $entryIndex % $employeeTasks->count()
                ];

                /*
                 * Generate a valid date based on both employee joining
                 * date and project start date.
                 */
                $dateOffset = (
                    ($employeeIndex * 2) + $entryIndex
                ) % 42;

                $projectStart = Carbon::parse($project->start_date);
                $employeeJoiningDate = Carbon::parse(
                    $employee->joining_date,
                );

                $earliestDate = $projectStart->greaterThan(
                    $employeeJoiningDate,
                )
                    ? $projectStart
                    : $employeeJoiningDate;

                $latestDate = now()->subDay();

                if ($earliestDate->greaterThan($latestDate)) {
                    continue;
                }

                $availableDays = $earliestDate->diffInDays(
                    $latestDate,
                );

                $workDate = $earliestDate->copy()
                    ->addDays(
                        $dateOffset % ($availableDays + 1),
                    );

                /*
                 * Four non-overlapping daily slots.
                 */
                $slots = [
                    ['09:00:00', '11:00:00'],
                    ['11:30:00', '13:30:00'],
                    ['14:00:00', '16:00:00'],
                    ['16:30:00', '18:30:00'],
                ];

                $slot = $slots[
                    ($employeeIndex + $entryIndex)
                    % count($slots)
                ];

                [$startTime, $endTime] = $slot;

                $workingMinutes = 120;

                /*
                 * Distribution:
                 *
                 * 70% approved
                 * 10% submitted
                 * 10% rejected
                 * 10% draft
                 */
                $status = match ($entryIndex % 10) {
                    7 => TimeEntryStatus::SUBMITTED,
                    8 => TimeEntryStatus::REJECTED,
                    9 => TimeEntryStatus::DRAFT,
                    default => TimeEntryStatus::APPROVED,
                };

                /*
                 * Every fifth entry is overtime.
                 */
                $entryType = $entryIndex % 5 === 0
                    ? TimeEntryType::OVERTIME
                    : TimeEntryType::REGULAR;

                $date = $workDate->toDateString();

                $submittedAt = $status !== TimeEntryStatus::DRAFT
                    ? "{$date} 19:00:00"
                    : null;

                $approvedAt = $status === TimeEntryStatus::APPROVED
                    ? "{$date} 20:00:00"
                    : null;

                $rejectedAt = $status === TimeEntryStatus::REJECTED
                    ? "{$date} 20:00:00"
                    : null;

                $rejectionReason = $status === TimeEntryStatus::REJECTED
                    ? 'Please review the submitted work details and resubmit.'
                    : null;

                $this->createTimeEntry(
                    employee: $employee,
                    project: $project,
                    task: $task,
                    workDate: $date,
                    startTime: $startTime,
                    endTime: $endTime,
                    breakMinutes: 0,
                    workingMinutes: $workingMinutes,
                    entryType: $entryType,
                    status: $status,
                    submittedAt: $submittedAt,
                    approvedAt: $approvedAt,
                    rejectionReason: $rejectionReason,
                    rejectedAt: $rejectedAt,
                );

                $created++;
            }
        }

        $this->command?->info(
            "Created {$created} additional time entries.",
        );
    }

    private function createTimeEntry(
        Employee $employee,
        Project $project,
        Task $task,
        string $workDate,
        string $startTime,
        string $endTime,
        int $breakMinutes,
        int $workingMinutes,
        TimeEntryType $entryType,
        TimeEntryStatus $status,
        ?string $submittedAt = null,
        ?string $approvedAt = null,
        ?string $rejectionReason = null,
        ?string $rejectedAt = null,
    ): TimeEntry {
        return TimeEntry::create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'task_id' => $task->id,
            'work_date' => $workDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'break_minutes' => $breakMinutes,
            'working_minutes' => $workingMinutes,
            'entry_type' => $entryType,
            'status' => $status,
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
            'rejected_at' => $rejectedAt,
            'rejection_reason' => $rejectionReason,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Approval History
    |--------------------------------------------------------------------------
    */

    private function createApprovalHistory(): void
    {
        $approvalService = app(TimeEntryApprovalService::class);

        /*
         * First process a small number of SUBMITTED entries through
         * the actual approval service.
         *
         * This is important because the seeded database should exercise
         * the same approval workflow used by the application.
         */
        $submittedEntries = TimeEntry::query()
            ->where('status', TimeEntryStatus::SUBMITTED)
            ->with([
                'employee',
                'project.projectManager.user',
            ])
            ->orderBy('id')
            ->limit(5)
            ->get();

        foreach ($submittedEntries as $entry) {
            $approver = $entry->project->projectManager?->user;

            if (! $approver) {
                continue;
            }

            /*
             * Never allow self approval.
             */
            if ($approver->id === $entry->employee->user_id) {
                continue;
            }

            $approvalService->approve(
                timeEntry: $entry,
                approver: $approver,
            );
        }

        /*
         * Directly seeded APPROVED entries represent historical
         * approved records. Their history is inserted manually because
         * approve() requires SUBMITTED status.
         */
        $approvedEntries = TimeEntry::query()
            ->where('status', TimeEntryStatus::APPROVED)
            ->with([
                'project.projectManager.user',
            ])
            ->get();

        foreach ($approvedEntries as $entry) {
            $alreadyRecorded = TimesheetApproval::query()
                ->where('time_entry_id', $entry->id)
                ->where('action', ApprovalAction::APPROVED)
                ->exists();

            if ($alreadyRecorded) {
                continue;
            }

            $approver = $entry->project->projectManager?->user;

            if (! $approver) {
                continue;
            }

            TimesheetApproval::create([
                'time_entry_id' => $entry->id,
                'approver_user_id' => $approver->id,
                'action' => ApprovalAction::APPROVED,
                'rejection_reason' => null,
                'acted_at' => $entry->approved_at ?? now(),
            ]);
        }

        /*
         * Rejected entries represent historical rejection scenarios.
         */
        $rejectedEntries = TimeEntry::query()
            ->where('status', TimeEntryStatus::REJECTED)
            ->with([
                'project.projectManager.user',
            ])
            ->get();

        foreach ($rejectedEntries as $entry) {
            $alreadyRecorded = TimesheetApproval::query()
                ->where('time_entry_id', $entry->id)
                ->where('action', ApprovalAction::REJECTED)
                ->exists();

            if ($alreadyRecorded) {
                continue;
            }

            $approver = $entry->project->projectManager?->user;

            if (! $approver) {
                continue;
            }

            TimesheetApproval::create([
                'time_entry_id' => $entry->id,
                'approver_user_id' => $approver->id,
                'action' => ApprovalAction::REJECTED,
                'rejection_reason' => $entry->rejection_reason,
                'acted_at' => $entry->rejected_at ?? now(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Payrolls
    |--------------------------------------------------------------------------
    */

    private function createPayrolls(User $admin): void
    {
        $payrollService = app(PayrollService::class);

        /*
         * Payroll eligible entries:
         *
         * APPROVED + REGULAR
         * APPROVED + OVERTIME
         *
         * Draft, submitted, rejected and cancelled entries are excluded.
         */
        $approvedEntries = TimeEntry::query()
            ->where('status', TimeEntryStatus::APPROVED)
            ->whereIn('entry_type', [
                TimeEntryType::REGULAR,
                TimeEntryType::OVERTIME,
            ])
            ->with('employee')
            ->orderBy('employee_id')
            ->orderBy('work_date')
            ->orderBy('id')
            ->get();

        if ($approvedEntries->isEmpty()) {
            $this->command?->warn(
                'No payroll-eligible approved time entries found. Payroll seeding skipped.',
            );

            return;
        }

        /*
         * Group by employee + calendar month.
         */
        $groups = $approvedEntries->groupBy(
            fn (TimeEntry $entry): string => sprintf(
                '%d-%s',
                $entry->employee_id,
                Carbon::parse($entry->work_date)->format('Y-m'),
            ),
        );

        $created = 0;
        $calculated = 0;
        $finalized = 0;

        foreach ($groups as $entries) {
            $firstEntry = $entries->first();

            if (! $firstEntry || ! $firstEntry->employee) {
                continue;
            }

            $employee = $firstEntry->employee;

            if ($employee->status !== EmployeeStatus::ACTIVE) {
                continue;
            }

            $periodStart = Carbon::parse($firstEntry->work_date)
                ->startOfMonth();

            $periodEnd = $periodStart
                ->copy()
                ->endOfMonth();

            /*
             * Re-check the actual database state.
             */
            $eligibleQuery = TimeEntry::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('work_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ])
                ->where('status', TimeEntryStatus::APPROVED)
                ->whereIn('entry_type', [
                    TimeEntryType::REGULAR,
                    TimeEntryType::OVERTIME,
                ]);

            $eligibleCount = $eligibleQuery->count();

            if ($eligibleCount === 0) {
                continue;
            }

            /*
             * The approval service may already have created a DRAFT
             * payroll for the employee/month.
             *
             * Reuse it rather than creating a duplicate payroll.
             */
            $payroll = Payroll::query()
                ->where('employee_id', $employee->id)
                ->whereDate(
                    'period_start',
                    $periodStart->toDateString(),
                )
                ->whereDate(
                    'period_end',
                    $periodEnd->toDateString(),
                )
                ->first();

            if ($payroll) {
                /*
                 * Never modify an already finalized payroll.
                 */
                if ($payroll->status === PayrollStatus::FINALIZED) {
                    continue;
                }
            } else {
                /*
                 * Create a new draft payroll.
                 */
                $payroll = $payrollService->create(
                    employee: $employee,
                    periodStart: $periodStart,
                    periodEnd: $periodEnd,
                    data: [
                        'overtime_multiplier' => '1.50',
                        'adjustment_amount' => $employee->id % 5 === 0
                            ? '1500.00'
                            : '0.00',
                        'deduction_amount' => $employee->id % 4 === 0
                            ? '750.00'
                            : '0.00',
                    ],
                );

                $created++;
            }

            /*
             * Always use the real PayrollService calculation.
             */
            $payroll = $payrollService->calculate($payroll);

            $calculated++;

            /*
             * Historical payrolls are finalized automatically.
             *
             * Current month remains DRAFT so it can be manually tested
             * through the Filament UI.
             */
            if ($periodStart->lt(now()->startOfMonth())) {
                $finalEligibleCount = TimeEntry::query()
                    ->where('employee_id', $employee->id)
                    ->whereBetween('work_date', [
                        $periodStart->toDateString(),
                        $periodEnd->toDateString(),
                    ])
                    ->where('status', TimeEntryStatus::APPROVED)
                    ->whereIn('entry_type', [
                        TimeEntryType::REGULAR,
                        TimeEntryType::OVERTIME,
                    ])
                    ->count();

                if ($finalEligibleCount === 0) {
                    continue;
                }

                /*
                 * Do not finalize if the payroll somehow became finalized
                 * between calculate() and this point.
                 */
                $payroll->refresh();

                if ($payroll->status === PayrollStatus::FINALIZED) {
                    continue;
                }

                $payrollService->finalize(
                    payroll: $payroll,
                    user: $admin,
                );

                $finalized++;
            }
        }

        $this->command?->info(
            "Payrolls: {$created} created, {$calculated} calculated, {$finalized} historical finalized.",
        );
    }
}