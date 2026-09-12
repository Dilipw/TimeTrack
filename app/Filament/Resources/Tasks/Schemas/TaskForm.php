<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\EmployeeStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Task;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->label('Project')
                    ->relationship(
                        name: 'project',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->whereIn('status', [
                                ProjectStatus::PLANNING,
                                ProjectStatus::ACTIVE,
                            ])
                            ->orderBy('name'),
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($set): void {
                        $set('parent_task_id', null);
                        $set('assignees', []);
                    })
                    ->required(),

                Select::make('parent_task_id')
                    ->label('Parent Task')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'title',
                        modifyQueryUsing: function ($query, Get $get, ?Task $record) {
                            $projectId = $get('project_id');

                            if (! $projectId) {
                                return $query->whereKey(0);
                            }

                            $query
                                ->where('project_id', $projectId)
                                ->whereNotIn('status', [
                                    TaskStatus::COMPLETED,
                                    TaskStatus::CANCELLED,
                                ]);

                            if ($record) {
                                $query->whereKeyNot($record->getKey());
                            }

                            return $query->orderBy('title');
                        },
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->disabled(fn (Get $get): bool => ! $get('project_id'))
                    ->placeholder('Select parent task'),

                TextInput::make('title')
                    ->label('Task Title')
                    ->required()
                    ->maxLength(255)
                    ->trim()
                    ->placeholder('e.g. Develop Authentication Module'),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(4)
                    ->maxLength(2000)
                    ->placeholder('Enter a brief description of the task...')
                    ->columnSpanFull(),

                Select::make('priority')
                    ->label('Priority')
                    ->options(TaskPriority::class)
                    ->default(TaskPriority::MEDIUM)
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options(TaskStatus::class)
                    ->default(TaskStatus::TODO)
                    ->required(),

                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->native(false)
                    ->nullable(),

                TextInput::make('estimated_minutes')
                    ->label('Estimated Time')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->nullable()
                    ->suffix('minutes'),

                Select::make('assignees')
                    ->label('Assignees')
                    ->multiple()
                    ->relationship(
                        name: 'assignees',
                        titleAttribute: 'employee_code',
                        modifyQueryUsing: function ($query, Get $get) {
                            $projectId = $get('project_id');

                            if (! $projectId) {
                                return $query->whereKey(0);
                            }

                            return $query
                                ->where('status', EmployeeStatus::ACTIVE)
                                ->whereHas('projectMemberships', function ($membershipQuery) use ($projectId): void {
                                    $membershipQuery
                                        ->where('project_id', $projectId)
                                        ->whereNull('removed_at');
                                })
                                ->orderBy('employee_code');
                        },
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Employee $record): string =>
                            "{$record->employee_code} - {$record->first_name} {$record->last_name}"
                    )
                    ->searchable([
                        'employee_code',
                        'first_name',
                        'last_name',
                    ])
                    ->preload()
                    ->disabled(fn (Get $get): bool => ! $get('project_id'))
                    ->placeholder('Select project members'),
            ]);
    }
}