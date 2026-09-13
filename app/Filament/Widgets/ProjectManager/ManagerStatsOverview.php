<?php

declare(strict_types=1);

namespace App\Filament\Widgets\ProjectManager;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user?->employee) {
            return [];
        }

        $stats = app(DashboardService::class)
            ->getManagerStats($user->employee);

        return [
            Stat::make(
                'Active Projects',
                $stats['active_projects']
            )
                ->description('Projects currently in progress')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('primary')
                ->icon('heroicon-o-briefcase'),

            Stat::make(
                'Team Members',
                $stats['team_members']
            )
                ->description('Active project members')
                ->descriptionIcon('heroicon-o-users')
                ->color('success')
                ->icon('heroicon-o-users'),

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
                'Tasks In Progress',
                $stats['tasks_in_progress']
            )
                ->description('Currently active tasks')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('info')
                ->icon('heroicon-o-arrow-path'),

            Stat::make(
                'Overdue Tasks',
                $stats['overdue_tasks']
            )
                ->description('Tasks past their due date')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color(
                    $stats['overdue_tasks'] > 0
                        ? 'danger'
                        : 'success'
                )
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}