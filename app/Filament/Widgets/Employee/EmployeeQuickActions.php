<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Employee;

use Filament\Actions\Action;
use Filament\Widgets\Widget;

class EmployeeQuickActions extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.employee.employee-quick-actions';

    public function logTimeUrl(): string
    {
        return route(
            'filament.user.resources.time-entries.create'
        );
    }

    public function tasksUrl(): string
    {
        return route(
            'filament.user.resources.tasks.index'
        );
    }

    public function timeEntriesUrl(): string
    {
        return route(
            'filament.user.resources.time-entries.index'
        );
    }
}