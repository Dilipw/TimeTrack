<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Enums\EmployeeStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('Sr. No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('employee_code')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('designation.name')
                    ->label('Designation')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('manager.employee_code')
                    ->label('Manager')
                    ->formatStateUsing(function (?string $state, $record): string {
                        if (! $record->manager) {
                            return '-';
                        }

                        return "{$record->manager->employee_code} - {$record->manager->first_name} {$record->manager->last_name}";
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('joining_date')
                    ->label('Joining Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->prefix('₹ ')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (EmployeeStatus $state): string => $state->name === 'ACTIVE'
                            ? 'Active'
                            : 'Inactive'
                    )
                    ->color(
                        fn (EmployeeStatus $state): string => $state === EmployeeStatus::ACTIVE
                            ? 'success'
                            : 'danger'
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
            ->defaultSort('employee_code');
    }
}
