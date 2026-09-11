<?php

namespace App\Filament\Resources\TimeEntries\Tables;

use App\Enums\TimeEntryStatus;
use App\Enums\TimeEntryType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Carbon\Carbon;


class TimeEntriesTable
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

                TextColumn::make('employee.employee_code')
                    ->label('Employee')
                    ->formatStateUsing(function (?string $state, $record): string {
                        if (! $record->employee) {
                            return '-';
                        }

                        return "{$record->employee->employee_code} - {$record->employee->first_name} {$record->employee->last_name}";
                    })
                    ->searchable([
                        'employee_code',
                        'first_name',
                        'last_name',
                    ])
                    ->sortable()
                    ->wrap()
                    ->limit(35),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(30),

                TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(40),

                TextColumn::make('work_date')
                    ->label('Work Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('work_time')
                    ->label('Time')
                    ->state(function ($record): string {
                        if (! $record->start_time || ! $record->end_time) {
                            return '-';
                        }

                        return sprintf(
                            '%s - %s',
                            Carbon::parse($record->start_time)->format('h:i A'),
                            Carbon::parse($record->end_time)->format('h:i A'),
                        );
                    })
                    ->sortable(false),

                TextColumn::make('break_minutes')
                    ->label('Break')
                    ->formatStateUsing(
                        fn(?int $state): string => $state === null
                            ? '-'
                            : "{$state} min"
                    )
                    ->sortable(),

                TextColumn::make('working_minutes')
                    ->label('Working Time')
                    ->formatStateUsing(
                        fn(?int $state): string => $state === null
                            ? '-'
                            : "{$state} min"
                    )
                    ->sortable(),

                TextColumn::make('entry_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn(TimeEntryType $state): string => match ($state) {
                            TimeEntryType::REGULAR => 'Regular',
                            TimeEntryType::OVERTIME => 'Overtime',
                        }
                    )
                    ->color(
                        fn(TimeEntryType $state): string => match ($state) {
                            TimeEntryType::REGULAR => 'gray',
                            TimeEntryType::OVERTIME => 'warning',
                        }
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn(TimeEntryStatus $state): string => match ($state) {
                            TimeEntryStatus::DRAFT => 'Draft',
                            TimeEntryStatus::SUBMITTED => 'Submitted',
                            TimeEntryStatus::APPROVED => 'Approved',
                            TimeEntryStatus::REJECTED => 'Rejected',
                            TimeEntryStatus::CANCELLED => 'Cancelled',
                        }
                    )
                    ->color(
                        fn(TimeEntryStatus $state): string => match ($state) {
                            TimeEntryStatus::DRAFT => 'gray',
                            TimeEntryStatus::SUBMITTED => 'info',
                            TimeEntryStatus::APPROVED => 'success',
                            TimeEntryStatus::REJECTED => 'danger',
                            TimeEntryStatus::CANCELLED => 'warning',
                        }
                    )
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rejected_at')
                    ->label('Rejected')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
            ->defaultSort('work_date', 'desc');
    }
}
