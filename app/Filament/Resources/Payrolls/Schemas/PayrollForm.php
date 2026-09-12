<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Employee')
                    ->relationship(
                        'employee',
                        'employee_code',
                        modifyQueryUsing: fn ($query) => $query
                            ->where('status', 'active')
                            ->orderBy('employee_code')
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

                DatePicker::make('period_start')
                    ->label('Period Start')
                    ->required(),

                DatePicker::make('period_end')
                    ->label('Period End')
                    ->after('period_start')
                    ->required(),

                TextInput::make('overtime_multiplier')
                    ->label('Overtime Multiplier')
                    ->required()
                    ->numeric()
                    ->default(1.5)
                    ->minValue(0.01)
                    ->maxValue(10)
                    ->step(0.01),

                TextInput::make('adjustment_amount')
                    ->label('Adjustment Amount')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->step(0.01)
                    ->prefix('₹'),

                TextInput::make('deduction_amount')
                    ->label('Deduction Amount')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('₹'),
            ]);
    }
}
