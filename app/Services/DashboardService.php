<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PayrollStatus;
use App\Enums\TimeEntryStatus;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Project;
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
}
