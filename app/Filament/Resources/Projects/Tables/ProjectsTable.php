<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('Sr. No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('project_code')
                    ->label('Project ID')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('name')
                    ->label('Project Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('projectManager.employee_code')
                    ->label('Project Manager')
                    ->formatStateUsing(function (?string $state, Project $record): string {
                        if (! $record->projectManager) {
                            return '-';
                        }

                        return "{$record->projectManager->employee_code} - {$record->projectManager->first_name} {$record->projectManager->last_name}";
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('status')
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
                    )
                    ->sortable(),

                TextColumn::make('budget')
                    ->label('Budget')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->prefix('₹ ')
                    ->placeholder('-')
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
            ->defaultSort('project_code');
    }
}
