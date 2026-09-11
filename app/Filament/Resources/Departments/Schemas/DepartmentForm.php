<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Department Name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->trim()
                    ->placeholder('e.g. Engineering'),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(4)
                    ->maxLength(1000)
                    ->placeholder('Enter a brief description of the department...')
                    ->columnSpanFull(),
            ]);
    }
}
