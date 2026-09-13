<?php

declare(strict_types=1);

namespace App\Filament\Widgets\ProjectManager;

use App\Models\Task;
use App\Services\DashboardService;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManagerTaskOverview extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $query = $user?->employee
            ? app(DashboardService::class)
                ->getManagerTasksQuery($user->employee)
            : Task::query()->whereKey(0);

        return $table
            ->query($query)
            ->heading('Task Overview')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Task')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => ucwords(
                            str_replace('_', ' ', $state->value)
                        )
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => ucwords(
                            str_replace('_', ' ', $state->value)
                        )
                    ),

                Tables\Columns\TextColumn::make('activeAssignees.first_name')
                    ->label('Assigned To')
                    ->formatStateUsing(
                        fn (Task $record): string => $record->activeAssignees
                            ->map(
                                fn ($employee): string => trim(
                                    $employee->first_name . ' ' .
                                    $employee->last_name
                                )
                            )
                            ->join(', ')
                    )
                    ->limit(35),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable()
                    ->color(
                        fn (Task $record): string =>
                            $record->due_date?->isPast()
                                ? 'danger'
                                : 'gray'
                    ),

                Tables\Columns\TextColumn::make('estimated_minutes')
                    ->label('Estimated')
                    ->formatStateUsing(
                        fn (?int $state): string => $state === null
                            ? '—'
                            : number_format($state / 60, 2) . 'h'
                    ),

                Tables\Columns\TextColumn::make('approved_actual_hours')
                    ->label('Approved Actual')
                    ->formatStateUsing(
                        fn (Task $record): string =>
                            number_format(
                                $record->approved_actual_hours,
                                2
                            ) . 'h'
                    ),
            ])
            ->actions([
                ViewAction::make()
                    ->url(
                        fn (Task $record): string => route(
                            'filament.user.resources.tasks.view',
                            ['record' => $record]
                        )
                    ),
            ])
            ->emptyStateHeading('No active tasks')
            ->emptyStateDescription(
                'There are no active tasks across your managed projects.'
            );
    }
}