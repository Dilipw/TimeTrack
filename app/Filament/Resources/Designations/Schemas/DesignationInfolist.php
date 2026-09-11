<?php

namespace App\Filament\Resources\Designations\Schemas;

use App\Models\Designation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DesignationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Designation Name')
                    ->weight('bold'),

                TextEntry::make('employees_count')
                    ->label('Employees')
                    ->state(fn (Designation $record): int => $record->employees()->count()),

                TextEntry::make('description')
                    ->label('Description')
                    ->placeholder('No description provided.')
                    ->columnSpanFull()
                    ->prose(),

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
                    ->visible(fn (Designation $record): bool => $record->trashed()),
            ]);
    }
}
