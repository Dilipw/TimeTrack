<?php

namespace App\Filament\Resources\TimeEntries\Schemas;

use App\Enums\TimeEntryType;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TimeEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Employee')
                    ->relationship(
                        name: 'employee',
                        titleAttribute: 'employee_code',
                        modifyQueryUsing: fn ($query) => $query
                            ->where('status', 'active')
                            ->orderBy('employee_code'),
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
                    ->required(),

                Select::make('project_id')
                    ->label('Project')
                    ->relationship(
                        name: 'project',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->where('status', 'active')
                            ->orderBy('name'),
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(
                        fn ($set) => $set('task_id', null)
                    )
                    ->required(),

                Select::make('task_id')
                    ->label('Task')
                    ->relationship(
                        name: 'task',
                        titleAttribute: 'title',
                        modifyQueryUsing: function ($query, Get $get) {
                            $projectId = $get('project_id');

                            if (! $projectId) {
                                return $query->whereKey(0);
                            }

                            return $query
                                ->where('project_id', $projectId)
                                ->whereNotIn('status', [
                                    'completed',
                                    'cancelled',
                                ])
                                ->orderBy('title');
                        },
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (Get $get): bool => ! $get('project_id')),

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
                        'Calculated automatically from start time, end time, and break.'
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
                        fn ($record): bool =>
                            $record?->status?->value === 'rejected'
                    )
                    ->columnSpanFull()
                    ->helperText(
                        'This entry was rejected and can be corrected and resubmitted.'
                    ),
            ]);
    }
}