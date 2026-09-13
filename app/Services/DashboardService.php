<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PayrollStatus;
use App\Enums\TimeEntryStatus;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    /**
     * Get system-wide statistics for admin dashboard.
     *
     * @return array{
     *     active_employees: int,
     *     inactive_employees: int,
     *     active_projects: int,
     *     pending_approvals: int,
     *     draft_payrolls: int,
     *     finalized_payrolls: int,
     *     current_month_payroll: float
     * }
     */
    public function getAdminStats(): array
    {
        return [
            'active_employees' => Employee::query()
                ->where('status', 'active')
                ->count(),

            'inactive_employees' => Employee::query()
                ->where('status', 'inactive')
                ->count(),

            'active_projects' => Project::query()
                ->where('status', 'active')
                ->count(),

            'pending_approvals' => TimeEntry::query()
                ->where('status', TimeEntryStatus::SUBMITTED)
                ->count(),

            'draft_payrolls' => Payroll::query()
                ->where('status', PayrollStatus::DRAFT)
                ->count(),

            'finalized_payrolls' => Payroll::query()
                ->where('status', PayrollStatus::FINALIZED)
                ->count(),

            'current_month_payroll' => (float) Payroll::query()
                ->where('status', PayrollStatus::FINALIZED)
                ->whereDate(
                    'period_start',
                    '>=',
                    now()->startOfMonth()
                )
                ->whereDate(
                    'period_end',
                    '<=',
                    now()->endOfMonth()
                )
                ->sum('net_amount'),
        ];
    }

    /**
     * Pending submitted time entries for admin dashboard.
     */
    public function getPendingApprovalsQuery(): Builder
    {
        return TimeEntry::query()
            ->with([
                'employee',
                'project',
                'task',
            ])
            ->where('status', TimeEntryStatus::SUBMITTED)
            ->latest('submitted_at');
    }

    /**
     * Get recent payrolls for admin dashboard.
     */
    public function getRecentPayrollsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Payroll::query()
            ->with('employee')
            ->latest('created_at');
    }

    /**
     * Get finalized payroll totals grouped by month.
     *
     * @return array{
     *     labels: array<int, string>,
     *     gross: array<int, float>,
     *     net: array<int, float>,
     * }
     */
    public function getAdminPayrollTrend(): array
    {
        $start = now()->startOfYear();
        $end = now()->endOfYear();

        $payrolls = Payroll::query()
            ->where('status', PayrollStatus::FINALIZED)
            ->whereDate('period_start', '>=', $start->toDateString())
            ->whereDate('period_end', '<=', $end->toDateString())
            ->get([
                'period_start',
                'gross_amount',
                'net_amount',
            ]);

        $months = collect(range(1, 12));

        return [
            'labels' => $months
                ->map(
                    fn(int $month): string =>
                    now()->setMonth($month)->format('M')
                )
                ->all(),

            'gross' => $months
                ->map(
                    fn(int $month): float =>
                    (float) $payrolls
                        ->filter(
                            fn($payroll): bool =>
                            $payroll->period_start->month === $month
                        )
                        ->sum('gross_amount')
                )
                ->all(),

            'net' => $months
                ->map(
                    fn(int $month): float =>
                    (float) $payrolls
                        ->filter(
                            fn($payroll): bool =>
                            $payroll->period_start->month === $month
                        )
                        ->sum('net_amount')
                )
                ->all(),
        ];
    }

    public function getManagerStats(Employee $manager): array
    {
        $projectIds = Project::query()
            ->where('project_manager_id', $manager->id)
            ->pluck('id');

        return [
            'active_projects' => Project::query()
                ->where('project_manager_id', $manager->id)
                ->where('status', 'active')
                ->count(),

            'team_members' => ProjectMember::query()
                ->whereIn('project_id', $projectIds)
                ->whereNull('removed_at')
                ->distinct('employee_id')
                ->count('employee_id'),

            'pending_approvals' => TimeEntry::query()
                ->whereIn('project_id', $projectIds)
                ->where('status', TimeEntryStatus::SUBMITTED)
                ->count(),

            'tasks_in_progress' => Task::query()
                ->whereIn('project_id', $projectIds)
                ->where('status', 'in_progress')
                ->count(),

            'overdue_tasks' => Task::query()
                ->whereIn('project_id', $projectIds)
                ->whereNotIn('status', [
                    'completed',
                    'cancelled',
                ])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];
    }

    /**
     * Get pending time approvals for projects managed by the manager.
     */
    public function getManagerPendingApprovalsQuery(Employee $manager): Builder
    {
        return TimeEntry::query()
            ->with([
                'employee',
                'project',
                'task',
            ])
            ->whereHas(
                'project',
                fn(Builder $query): Builder => $query
                    ->where('project_manager_id', $manager->id)
            )
            ->where('status', TimeEntryStatus::SUBMITTED)
            ->latest('submitted_at');
    }

    /**
     * Get projects managed by the given project manager.
     */
    public function getManagerProjectsQuery(Employee $manager): Builder
    {
        return Project::query()
            ->withCount([
                'members as active_members_count' => fn(Builder $query): Builder =>
                $query->whereNull('removed_at'),
                'tasks',
            ])
            ->where('project_manager_id', $manager->id)
            ->latest('created_at');
    }

    /**
     * Get task overview for projects managed by the given project manager.
     */
    public function getManagerTasksQuery(Employee $manager): Builder
    {
        return Task::query()
            ->with([
                'project',
                'activeAssignees',
            ])
            ->whereHas(
                'project',
                fn(Builder $query): Builder => $query
                    ->where('project_manager_id', $manager->id)
            )
            ->whereNotIn('status', [
                'completed',
                'cancelled',
            ])
            ->latest('due_date');
    }

    /**
     * Get personal statistics for the employee dashboard.
     *
     * @return array{
     *     active_projects: int,
     *     assigned_tasks: int,
     *     today_hours: float,
     *     current_month_approved_hours: float,
     *     pending_entries: int,
     *     rejected_entries: int
     * }
     */
    public function getEmployeeStats(Employee $employee): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        return [
            'active_projects' => ProjectMember::query()
                ->where('employee_id', $employee->id)
                ->whereNull('removed_at')
                ->whereHas(
                    'project',
                    fn(Builder $query): Builder => $query
                        ->where('status', 'active')
                )
                ->count(),

            'assigned_tasks' => Task::query()
                ->whereHas(
                    'activeAssignees',
                    fn(Builder $query): Builder => $query
                        ->where('employees.id', $employee->id)
                )
                ->whereNotIn('status', [
                    'completed',
                    'cancelled',
                ])
                ->count(),

            'today_hours' => round(
                TimeEntry::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('work_date', $today)
                    ->where('status', TimeEntryStatus::APPROVED)
                    ->sum('working_minutes') / 60,
                2
            ),

            'current_month_approved_hours' => round(
                TimeEntry::query()
                    ->where('employee_id', $employee->id)
                    ->whereBetween('work_date', [$monthStart, $monthEnd])
                    ->where('status', TimeEntryStatus::APPROVED)
                    ->sum('working_minutes') / 60,
                2
            ),

            'pending_entries' => TimeEntry::query()
                ->where('employee_id', $employee->id)
                ->where('status', TimeEntryStatus::SUBMITTED)
                ->count(),

            'rejected_entries' => TimeEntry::query()
                ->where('employee_id', $employee->id)
                ->where('status', TimeEntryStatus::REJECTED)
                ->count(),
        ];
    }

    /**
     * Get active tasks assigned to the given employee.
     */
    public function getEmployeeTasksQuery(Employee $employee): Builder
    {
        return Task::query()
            ->with([
                'project',
                'activeAssignees',
            ])
            ->whereHas(
                'activeAssignees',
                fn(Builder $query): Builder => $query
                    ->where('employees.id', $employee->id)
            )
            ->whereNotIn('status', [
                'completed',
                'cancelled',
            ])
            ->orderByRaw(
                'CASE WHEN due_date IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy('due_date');
    }

    /**
     * Get recent time entries for the given employee.
     */
    public function getEmployeeTimeEntriesQuery(Employee $employee): Builder
    {
        return TimeEntry::query()
            ->with([
                'project',
                'task',
            ])
            ->where('employee_id', $employee->id)
            ->latest('work_date')
            ->latest('created_at');
    }
}
