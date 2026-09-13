<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\Admin\AdminPendingApprovals;
use App\Filament\Widgets\Admin\AdminStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\Admin\AdminRecentPayrolls;

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
