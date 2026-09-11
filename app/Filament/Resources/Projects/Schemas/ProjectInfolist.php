<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('project_code')
                    ->label('Project ID')
                    ->weight('bold'),

                TextEntry::make('name')
                    ->label('Project Name')
                    ->weight('bold'),

                TextEntry::make('projectManager.employee_code')
                    ->label('Project Manager')
                    ->formatStateUsing(function (?string $state, Project $record): string {
                        if (! $record->projectManager) {
                            return '-';
                        }

                        return "{$record->projectManager->employee_code} - {$record->projectManager->first_name} {$record->projectManager->last_name}";
                    }),

                TextEntry::make('description')
                    ->label('Description')
                    ->placeholder('No description provided.')
                    ->columnSpanFull()
                    ->prose(),

                TextEntry::make('start_date')
                    ->label('Start Date')
                    ->date('d M Y'),

                TextEntry::make('end_date')
                    ->label('End Date')
                    ->date('d M Y')
                    ->placeholder('-'),

                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (ProjectStatus $state): string => match ($state) {
                            ProjectStatus::PLANNING => 'Planning',
                            ProjectStatus::ACTIVE => 'Active',
                            ProjectStatus::COMPLETED => 'Completed',
                            ProjectStatus::CANCELLED => 'Cancelled',
                        }
                    )
                    ->color(
                        fn (ProjectStatus $state): string => match ($state) {
                            ProjectStatus::PLANNING => 'gray',
                            ProjectStatus::ACTIVE => 'success',
                            ProjectStatus::COMPLETED => 'info',
                            ProjectStatus::CANCELLED => 'danger',
                        }
                    ),

                TextEntry::make('budget')
                    ->label('Budget')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->prefix('₹ ')
                    ->placeholder('-'),

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
                    ->visible(fn (Project $record): bool => $record->trashed()),
            ]);
    }
}
