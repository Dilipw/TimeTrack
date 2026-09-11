<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_code')
                    ->label('Employee ID')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->trim()
                    ->placeholder('e.g. EMP001'),

                Select::make('user_id')
                    ->label('User Account')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->whereDoesntHave('employee')
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(100)
                    ->trim()
                    ->placeholder('e.g. Rahul'),

                TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(100)
                    ->trim()
                    ->placeholder('e.g. Sharma'),

                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('designation_id')
                    ->label('Designation')
                    ->relationship('designation', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('manager_id')
                    ->label('Manager')
                    ->relationship(
                        name: 'manager',
                        titleAttribute: 'employee_code',
                        modifyQueryUsing: fn ($query, ?Employee $record) => $record
                            ? $query->whereKeyNot($record->getKey())
                            : $query
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Employee $record): string => "{$record->employee_code} - {$record->first_name} {$record->last_name}"
                    )
                    ->searchable(['employee_code', 'first_name', 'last_name'])
                    ->preload()
                    ->nullable()
                    ->placeholder('Select manager'),

                DatePicker::make('joining_date')
                    ->label('Joining Date')
                    ->required()
                    ->native(false)
                    ->maxDate(now()),

                TextInput::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(9999999999.99)
                    ->step(0.01)
                    ->prefix('₹'),

                Select::make('status')
                    ->label('Status')
                    ->options(EmployeeStatus::class)
                    ->default(EmployeeStatus::ACTIVE)
                    ->required(),
            ]);
    }
}
