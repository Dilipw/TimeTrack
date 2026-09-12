<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Schemas;

use App\Models\Payroll;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PayrollInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.employee_code')
                    ->label('Employee ID'),

                TextEntry::make('employee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(
                        fn ($state, Payroll $record): string =>
                            "{$record->employee->first_name} {$record->employee->last_name}"
                    ),

                TextEntry::make('period_start')
                    ->label('Period Start')
                    ->date('d M Y'),

                TextEntry::make('period_end')
                    ->label('Period End')
                    ->date('d M Y'),

                TextEntry::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    ),

                TextEntry::make('overtime_multiplier')
                    ->label('OT Multiplier')
                    ->formatStateUsing(
                        fn ($state): string => number_format((float) $state, 2) . '×'
                    ),

                TextEntry::make('regular_minutes')
                    ->label('Regular Hours')
                    ->formatStateUsing(
                        fn (int $state): string =>
                            sprintf('%d h %02d m', intdiv($state, 60), $state % 60)
                    ),

                TextEntry::make('overtime_minutes')
                    ->label('Overtime Hours')
                    ->formatStateUsing(
                        fn (int $state): string =>
                            sprintf('%d h %02d m', intdiv($state, 60), $state % 60)
                    ),

                TextEntry::make('regular_amount')
                    ->label('Regular Amount')
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    ),

                TextEntry::make('overtime_amount')
                    ->label('Overtime Amount')
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    ),

                TextEntry::make('adjustment_amount')
                    ->label('Adjustment')
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    ),

                TextEntry::make('deduction_amount')
                    ->label('Deduction')
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    ),

                TextEntry::make('gross_amount')
                    ->label('Gross Amount')
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    ),

                TextEntry::make('net_amount')
                    ->label('Net Amount')
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    ),

                TextEntry::make('status')
                    ->label('Status')
                    ->badge(),

                TextEntry::make('finalized_at')
                    ->label('Finalized At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('finalizedBy.name')
                    ->label('Finalized By')
                    ->placeholder('-'),

                TextEntry::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-')
                    ->visible(
                        fn (Payroll $record): bool => $record->trashed()
                    ),

                RepeatableEntry::make('timeEntries')
                    ->label('Included Time Entries')
                    ->schema([
                        TextEntry::make('timeEntry.work_date')
                            ->label('Work Date')
                            ->date('d M Y'),

                        TextEntry::make('timeEntry.task.title')
                            ->label('Task'),

                        TextEntry::make('entry_type')
                            ->label('Type')
                            ->badge(),

                        TextEntry::make('working_minutes')
                            ->label('Working Time')
                            ->formatStateUsing(
                                fn (int $state): string =>
                                    sprintf(
                                        '%d h %02d m',
                                        intdiv($state, 60),
                                        $state % 60
                                    )
                            ),

                        TextEntry::make('hourly_rate')
                            ->label('Rate')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    '₹' . number_format((float) $state, 2)
                            ),

                        TextEntry::make('amount')
                            ->label('Amount')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    '₹' . number_format((float) $state, 2)
                            ),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(
                        fn (Payroll $record): bool =>
                            $record->timeEntries()->exists()
                    ),
            ]);
    }
}
