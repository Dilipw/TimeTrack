<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Employee;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user?->employee) {
            return [];
        }

        $stats = app(DashboardService::class)
            ->getEmployeeStats($user->employee);

        return [
            Stat::make(
                'Active Projects',
                $stats['active_projects']
            )
                ->description('Projects you are currently working on')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('primary')
                ->icon('heroicon-o-briefcase'),

            Stat::make(
                'Assigned Tasks',
                $stats['assigned_tasks']
            )
                ->description('Your active tasks')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('info')
                ->icon('heroicon-o-clipboard-document-list'),

            Stat::make(
                "Today's Hours",
                number_format($stats['today_hours'], 2) . 'h'
            )
                ->description('Approved hours for today')
                ->descriptionIcon('heroicon-o-clock')
                ->color('success')
                ->icon('heroicon-o-clock'),

            Stat::make(
                'Approved Hours',
                number_format(
                    $stats['current_month_approved_hours'],
                    2
                ) . 'h'
            )
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make(
                'Pending Entries',
                $stats['pending_entries']
            )
                ->description('Waiting for approval')
                ->descriptionIcon('heroicon-o-arrow-up-tray')
                ->color(
                    $stats['pending_entries'] > 0
                        ? 'warning'
                        : 'success'
                )
                ->icon('heroicon-o-arrow-up-tray'),

            Stat::make(
                'Rejected Entries',
                $stats['rejected_entries']
            )
                ->description('Entries requiring attention')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color(
                    $stats['rejected_entries'] > 0
                        ? 'danger'
                        : 'success'
                )
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}