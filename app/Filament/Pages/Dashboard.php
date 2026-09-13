<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\Admin\AdminPendingApprovals;
use App\Filament\Widgets\Admin\AdminPayrollTrend;
use App\Filament\Widgets\Admin\AdminRecentPayrolls;
use App\Filament\Widgets\Admin\AdminStatsOverview;
use App\Filament\Widgets\ProjectManager\ManagerStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\ProjectManager\ManagerPendingApprovals;
use App\Filament\Widgets\ProjectManager\ManagerProjects;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return [
                AdminStatsOverview::class,
                AdminPendingApprovals::class,
                AdminRecentPayrolls::class,
                AdminPayrollTrend::class,
            ];
        }

        if ($user->hasRole('project_manager')) {
            return [
                ManagerStatsOverview::class,
                ManagerPendingApprovals::class,
                ManagerProjects::class,
            ];
        }

        return [];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
