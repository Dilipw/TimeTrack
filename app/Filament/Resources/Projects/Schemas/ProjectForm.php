<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectStatus;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('project_code')
                    ->label('Project ID')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->trim()
                    ->placeholder('e.g. PRJ-0001'),

                TextInput::make('name')
                    ->label('Project Name')
                    ->required()
                    ->maxLength(255)
                    ->trim()
                    ->placeholder('e.g. Client Portal'),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(4)
                    ->maxLength(2000)
                    ->placeholder('Enter a brief description of the project...')
                    ->columnSpanFull(),

                Select::make('project_manager_id')
                    ->label('Project Manager')
                    ->relationship(
                        name: 'projectManager',
                        titleAttribute: 'employee_code',
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Employee $record): string => "{$record->employee_code} - {$record->first_name} {$record->last_name}"
                    )
                    ->searchable(['employee_code', 'first_name', 'last_name'])
                    ->preload()
                    ->required(),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->native(false),

                DatePicker::make('end_date')
                    ->label('End Date')
                    ->native(false)
                    ->afterOrEqual('start_date')
                    ->nullable(),

                Select::make('status')
                    ->label('Status')
                    ->options(ProjectStatus::class)
                    ->default(ProjectStatus::PLANNING)
                    ->required(),

                TextInput::make('budget')
                    ->label('Budget')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(9999999999.99)
                    ->step(0.01)
                    ->prefix('₹ ')
                    ->nullable(),
            ]);
    }
}
