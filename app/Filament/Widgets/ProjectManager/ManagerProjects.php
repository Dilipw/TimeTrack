<?php

declare(strict_types=1);

namespace App\Filament\Widgets\ProjectManager;

use App\Models\Employee;
use App\Models\Project;
use App\Services\DashboardService;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManagerProjects extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $query = $user?->employee
            ? app(DashboardService::class)
                ->getManagerProjectsQuery($user->employee)
            : Project::query()->whereKey(0);

        return $table
            ->query($query)
            ->heading('My Projects')
            ->columns([
                Tables\Columns\TextColumn::make('project_code')
                    ->label('Project ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Project')
                    ->searchable()
                    ->limit(35),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => ucwords(
                            str_replace('_', ' ', $state->value)
                        )
                    ),

                Tables\Columns\TextColumn::make('active_members_count')
                    ->label('Members')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make()
                    ->url(
                        fn (Project $record): string => route(
                            'filament.user.resources.projects.view',
                            ['record' => $record]
                        )
                    ),
            ])
            ->emptyStateHeading('No projects assigned')
            ->emptyStateDescription(
                'You are not currently managing any projects.'
            );
    }
}