<?php

declare(strict_types=1);

namespace App\Filament\Widgets\ProjectManager;

use App\Enums\TimeEntryStatus;
use App\Models\TimeEntry;
use App\Services\DashboardService;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManagerPendingApprovals extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $query = $user?->employee
            ? app(DashboardService::class)
                ->getManagerPendingApprovalsQuery($user->employee)
            : TimeEntry::query()->whereKey(0);

        return $table
            ->query($query)
            ->heading('Pending Time Approvals')
            ->columns([
                Tables\Columns\TextColumn::make('employee.employee_code')
                    ->label('Employee')
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Name')
                    ->formatStateUsing(
                        fn ($record): string => trim(
                            $record->employee->first_name . ' ' .
                            $record->employee->last_name
                        )
                    ),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable(),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->limit(35),

                Tables\Columns\TextColumn::make('work_date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('working_minutes')
                    ->label('Hours')
                    ->formatStateUsing(
                        fn (int $state): string =>
                            number_format($state / 60, 2) . 'h'
                    ),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (TimeEntryStatus $state): string =>
                            ucwords(str_replace('_', ' ', $state->value))
                    ),
            ])
            ->actions([
                ViewAction::make()
                    ->url(
                        fn ($record): string => route(
                            'filament.user.resources.time-entries.view',
                            ['record' => $record]
                        )
                    ),
            ])
            ->emptyStateHeading('No pending approvals')
            ->emptyStateDescription(
                'There are no submitted time entries awaiting your approval.'
            );
    }
}