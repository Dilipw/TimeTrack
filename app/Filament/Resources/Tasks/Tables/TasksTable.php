<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Task;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use App\Enums\TimeEntryStatus;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('Sr. No.')
                    ->rowIndex()
                    ->alignCenter()
                    ->width('70px'),

                TextColumn::make('title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->limit(45),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->wrap(),

                TextColumn::make('assignees')
                    ->label('Assignees')
                    ->state(function (Task $record): string {
                        $assignees = $record->activeAssignees;

                        if ($assignees->isEmpty()) {
                            return '-';
                        }

                        $visibleAssignees = $assignees->take(2);

                        $names = $visibleAssignees
                            ->map(
                                fn($employee): string =>
                                "{$employee->first_name} {$employee->last_name}"
                            )
                            ->implode(', ');

                        $remaining = $assignees->count() - $visibleAssignees->count();

                        return $remaining > 0
                            ? "{$names} +{$remaining}"
                            : $names;
                    })
                    ->url(function (Task $record): ?string {
                        $employee = $record->activeAssignees->first();

                        return $employee
                            ? EmployeeResource::getUrl('view', [
                                'record' => $employee,
                            ])
                            : null;
                    })
                    ->color('primary')
                    ->icon('heroicon-m-user-group')
                    ->wrap()
                    ->limit(45),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(
                        fn(TaskPriority $state): string => match ($state) {
                            TaskPriority::LOW => 'Low',
                            TaskPriority::MEDIUM => 'Medium',
                            TaskPriority::HIGH => 'High',
                            TaskPriority::URGENT => 'Urgent',
                        }
                    )
                    ->color(
                        fn(TaskPriority $state): string => match ($state) {
                            TaskPriority::LOW => 'gray',
                            TaskPriority::MEDIUM => 'info',
                            TaskPriority::HIGH => 'warning',
                            TaskPriority::URGENT => 'danger',
                        }
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn(TaskStatus $state): string => match ($state) {
                            TaskStatus::TODO => 'To Do',
                            TaskStatus::IN_PROGRESS => 'In Progress',
                            TaskStatus::COMPLETED => 'Completed',
                            TaskStatus::CANCELLED => 'Cancelled',
                        }
                    )
                    ->color(
                        fn(TaskStatus $state): string => match ($state) {
                            TaskStatus::TODO => 'gray',
                            TaskStatus::IN_PROGRESS => 'info',
                            TaskStatus::COMPLETED => 'success',
                            TaskStatus::CANCELLED => 'danger',
                        }
                    )
                    ->sortable(),

                TextColumn::make('parent.title')
                    ->label('Parent Task')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('approved_actual_minutes')
                    ->label('Approved Actual')
                    ->state(fn(Task $record): int => (int) $record->timeEntries()
                        ->where('status', TimeEntryStatus::APPROVED->value)
                        ->sum('working_minutes'))
                    ->formatStateUsing(
                        fn(int $state): string => sprintf(
                            '%dh %02dm',
                            intdiv($state, 60),
                            $state % 60,
                        )
                    )
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('title');
    }
}
