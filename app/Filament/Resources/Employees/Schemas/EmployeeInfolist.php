<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee_code')
                    ->label('Employee ID')
                    ->weight('bold'),

                TextEntry::make('user.name')
                    ->label('User Account')
                    ->placeholder('-'),

                TextEntry::make('first_name')
                    ->label('First Name'),

                TextEntry::make('last_name')
                    ->label('Last Name'),

                TextEntry::make('department.name')
                    ->label('Department')
                    ->placeholder('-'),

                TextEntry::make('designation.name')
                    ->label('Designation')
                    ->placeholder('-'),

                TextEntry::make('manager.employee_code')
                    ->label('Manager')
                    ->formatStateUsing(function (?string $state, Employee $record): string {
                        if (! $record->manager) {
                            return '-';
                        }

                        return "{$record->manager->employee_code} - {$record->manager->first_name} {$record->manager->last_name}";
                    }),

                TextEntry::make('joining_date')
                    ->label('Joining Date')
                    ->date('d M Y'),

                TextEntry::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->prefix('₹ '),

                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (EmployeeStatus $state): string => $state === EmployeeStatus::ACTIVE
                            ? 'Active'
                            : 'Inactive'
                    )
                    ->color(
                        fn (EmployeeStatus $state): string => $state === EmployeeStatus::ACTIVE
                            ? 'success'
                            : 'danger'
                    ),

                TextEntry::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('d M Y, h:i A')
                    ->visible(fn (Employee $record): bool => $record->trashed()),
            ]);
    }
}
