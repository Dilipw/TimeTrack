<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Admin;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class AdminPayrollTrend extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Payroll Trend';

    protected function getData(): array
    {
        $data = app(DashboardService::class)
            ->getAdminPayrollTrend();

        return [
            'datasets' => [
                [
                    'label' => 'Gross Payroll',
                    'data' => $data['gross'],
                ],
                [
                    'label' => 'Net Payroll',
                    'data' => $data['net'],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}