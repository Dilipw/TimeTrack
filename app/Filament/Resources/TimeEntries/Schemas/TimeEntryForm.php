<?php

declare(strict_types=1);

namespace App\Filament\Resources\TimeEntries\Schemas;

use App\Enums\EmployeeStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\TimeEntryType;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TimeEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_id')
                ->label('Employee')
                ->relationship(
                    name: 'employee',
                    titleAttribute: 'employee_code',
                    modifyQueryUsing: function ($query): void {
                        $query->where('status', EmployeeStatus::ACTIVE);

                        $user = auth()->user();

                        if (! $user) {
                            $query->whereKey(0);

                            return;
                        }

                        // Admin can select any active employee.
                        if ($user->hasAnyRole(['super_admin', 'admin'])) {
                            $query->orderBy('employee_code');

                            return;
                        }

                        // Employee can select only themselves.
                        if ($user->hasRole('employee')) {
                            $employeeId = $user->employee?->id;

                            if ($employeeId) {
                                $query->whereKey($employeeId);
                            } else {
                                $query->whereKey(0);
                            }

                            return;
                        }

                        // PM can select active employees.
                        if ($user->hasRole('project_manager')) {
                            $query->orderBy('employee_code');

                            return;
                        }

                        $query->whereKey(0);
                    },
                )
                ->getOptionLabelFromRecordUsing(
                    fn(Employee $record): string => "{$record->employee_code} - {$record->first_name} {$record->last_name}",
                )
                ->default(
                    fn(): ?int => auth()->user()?->employee?->id,
                )
                ->searchable()
                ->preload()
                ->required()
                ->disabled(
                    fn(): bool => auth()->user()?->hasRole('employee') ?? false,
                )
                // Important: disabled employee field must still be submitted.
                ->dehydrated(true),

            Select::make('project_id')
                ->label('Project')
                ->relationship(
                    name: 'project',
                    titleAttribute: 'name',
                    modifyQueryUsing: function ($query): void {
                        $query
                            ->where('status', ProjectStatus::ACTIVE);

                        $user = auth()->user();

                        if (! $user) {
                            $query->whereKey(0);

                            return;
                        }

                        // Admin can log against any active project.
                        if ($user->hasAnyRole(['super_admin', 'admin'])) {
                            $query->orderBy('name');

                            return;
                        }

                        $employee = $user->employee;

                        if (! $employee) {
                            $query->whereKey(0);

                            return;
                        }

                        // Employee can log only against active projects
                        // where they are currently a member.
                        if ($user->hasRole('employee')) {
                            $query
                                ->whereHas(
                                    'activeMembers',
                                    fn($memberQuery) => $memberQuery
                                        ->whereKey($employee->id),
                                )
                                ->orderBy('name');

                            return;
                        }

                        // PM can log against projects they manage.
                        if ($user->hasRole('project_manager')) {
                            $query
                                ->where('project_manager_id', $employee->id)
                                ->orderBy('name');

                            return;
                        }

                        $query->whereKey(0);
                    },
                )
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(
                    fn($set): mixed => $set('task_id', null),
                )
                ->required(),

            Select::make('task_id')
                ->label('Task')
                ->relationship(
                    name: 'task',
                    titleAttribute: 'title',
                    modifyQueryUsing: function ($query, Get $get): void {
                        $projectId = $get('project_id');

                        if (! $projectId) {
                            $query->whereKey(0);

                            return;
                        }

                        $query
                            ->where('project_id', $projectId)
                            ->whereNotIn('status', [
                                TaskStatus::COMPLETED,
                                TaskStatus::CANCELLED,
                            ]);

                        $user = auth()->user();

                        if (! $user) {
                            $query->whereKey(0);

                            return;
                        }

                        // Employee can select only tasks assigned to themselves.
                        if ($user->hasRole('employee')) {
                            $employeeId = $user->employee?->id;

                            if ($employeeId) {
                                $query->whereHas(
                                    'activeAssignees',
                                    fn($assigneeQuery) => $assigneeQuery
                                        ->whereKey($employeeId),
                                );
                            } else {
                                $query->whereKey(0);
                            }
                        }

                        // PM sees tasks from their managed project.
                        if ($user->hasRole('project_manager')) {
                            $employeeId = $user->employee?->id;

                            if ($employeeId) {
                                $query->whereHas(
                                    'project',
                                    fn($projectQuery) => $projectQuery
                                        ->where('project_manager_id', $employeeId),
                                );
                            } else {
                                $query->whereKey(0);
                            }
                        }

                        $query->orderBy('title');
                    },
                )
                ->searchable()
                ->preload()
                ->required()
                ->disabled(
                    fn(Get $get): bool => ! $get('project_id'),
                ),

            DatePicker::make('work_date')
                ->label('Work Date')
                ->required()
                ->native(false)
                ->maxDate(now()),

            TimePicker::make('start_time')
                ->label('Start Time')
                ->required()
                ->seconds(false)
                ->native(false),

            TimePicker::make('end_time')
                ->label('End Time')
                ->required()
                ->seconds(false)
                ->native(false),

            TextInput::make('break_minutes')
                ->label('Break')
                ->required()
                ->numeric()
                ->integer()
                ->minValue(0)
                ->default(0)
                ->suffix('minutes'),

            TextInput::make('working_minutes')
                ->label('Working Time')
                ->disabled()
                ->dehydrated(false)
                ->placeholder('Calculated automatically')
                ->suffix('minutes')
                ->helperText(
                    'Calculated automatically from start time, end time, and break.',
                ),

            Select::make('entry_type')
                ->label('Entry Type')
                ->options(TimeEntryType::class)
                ->required(),

            Textarea::make('rejection_reason')
                ->label('Rejection Reason')
                ->disabled()
                ->dehydrated(false)
                ->rows(3)
                ->visible(
                    fn($record): bool =>
                    $record?->status?->value === 'rejected',
                )
                ->columnSpanFull()
                ->helperText(
                    'This entry was rejected and can be corrected and resubmitted.',
                ),
        ]);
    }
}
