<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Select::make('roles')
                    ->label('Role')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query
                            ->whereIn('name', [
                                'admin',
                                'project_manager',
                                'employee',
                            ])
                            ->orderBy('name'),
                    )
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required()
                    ->getOptionLabelFromRecordUsing(
                        fn($record): string => str($record->name)
                            ->replace('_', ' ')
                            ->title()
                            ->toString(),
                    ),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->rule(Password::defaults())
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->dehydrated(fn(?string $state): bool => filled($state))
                    ->confirmed()
                    ->maxLength(255)
                    ->helperText(
                        fn(string $operation): string => $operation === 'edit'
                            ? 'Leave blank to keep the current password.'
                            : 'Set a secure password for this account.',
                    ),

                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->revealable()
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->dehydrated(false)
                    ->maxLength(255),
            ]);
    }
}
