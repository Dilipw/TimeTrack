<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Admin;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = app(DashboardService::class)->getAdminStats();

        return [
            Stat::make(
                'Active Employees',
                $stats['active_employees']
            )
                ->description(
                    "{$stats['inactive_employees']} inactive"
                )
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->icon('heroicon-o-users'),

            Stat::make(
                'Active Projects',
                $stats['active_projects']
            )
                ->description('Currently active projects')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('primary')
                ->icon('heroicon-o-briefcase'),

            Stat::make(
                'Pending Approvals',
                $stats['pending_approvals']
            )
                ->description('Time entries awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color(
                    $stats['pending_approvals'] > 0
                        ? 'warning'
                        : 'success'
                )
                ->icon('heroicon-o-clock'),

            Stat::make(
                'Draft Payrolls',
                $stats['draft_payrolls']
            )
                ->description('Payrolls awaiting finalization')
                ->descriptionIcon('heroicon-o-document-text')
                ->color(
                    $stats['draft_payrolls'] > 0
                        ? 'warning'
                        : 'success'
                )
                ->icon('heroicon-o-document-text'),

            Stat::make(
                'Finalized Payrolls',
                $stats['finalized_payrolls']
            )
                ->description('Historical payroll records')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make(
                'Current Month Payroll',
                '₹' . number_format(
                    $stats['current_month_payroll'],
                    2
                )
            )
                ->description(
                    now()->format('F Y')
                )
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}