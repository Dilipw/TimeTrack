<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Tables;

use App\Enums\PayrollStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('Sr No.')
                    ->rowIndex(),

                TextColumn::make('employee.employee_code')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(
                        fn ($state, $record): string =>
                            "{$record->employee->first_name} {$record->employee->last_name}"
                    )
                    ->searchable([
                        'first_name',
                        'last_name',
                    ]),

                TextColumn::make('period_start')
                    ->label('Period Start')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('period_end')
                    ->label('Period End')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('regular_minutes')
                    ->label('Regular Hours')
                    ->formatStateUsing(
                        fn (int $state): string =>
                            sprintf('%d h %02d m', intdiv($state, 60), $state % 60)
                    ),

                TextColumn::make('overtime_minutes')
                    ->label('OT Hours')
                    ->formatStateUsing(
                        fn (int $state): string =>
                            sprintf('%d h %02d m', intdiv($state, 60), $state % 60)
                    ),

                TextColumn::make('gross_amount')
                    ->label('Gross')
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    )
                    ->sortable(),

                TextColumn::make('net_amount')
                    ->label('Net')
                    ->formatStateUsing(
                        fn ($state): string =>
                            '₹' . number_format((float) $state, 2)
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (PayrollStatus $state): string =>
                            str($state->value)->replace('_', ' ')->title()->toString()
                    )
                    ->color(
                        fn (PayrollStatus $state): string => match ($state) {
                            PayrollStatus::DRAFT => 'warning',
                            PayrollStatus::FINALIZED => 'success',
                        }
                    ),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('finalized_at')
                    ->label('Finalized')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PayrollStatus::class),

                SelectFilter::make('employee')
                    ->relationship('employee', 'employee_code')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->defaultSort('period_start', 'desc');
    }
}
