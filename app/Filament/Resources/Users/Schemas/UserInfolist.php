<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Full Name'),

                TextEntry::make('email')
                    ->label('Email Address'),

                TextEntry::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => $state
                            ? str($state)->replace('_', ' ')->title()->toString()
                            : '-',
                    )
                    ->placeholder('-'),

                TextEntry::make('employee.employee_code')
                    ->label('Employee ID')
                    ->placeholder('-'),

                TextEntry::make('employee.department.name')
                    ->label('Department')
                    ->placeholder('-'),

                TextEntry::make('employee.designation.name')
                    ->label('Designation')
                    ->placeholder('-'),

                TextEntry::make('employee.status')
                    ->label('Employee Status')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => $state?->value
                            ? str($state->value)->title()->toString()
                            : '-',
                    )
                    ->placeholder('-'),

                TextEntry::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),
            ]);
    }
}
