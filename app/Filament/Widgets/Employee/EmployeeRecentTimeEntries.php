<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Employee;

use App\Models\TimeEntry;
use App\Services\DashboardService;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class EmployeeRecentTimeEntries extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $query = $user?->employee
            ? app(DashboardService::class)
                ->getEmployeeTimeEntriesQuery($user->employee)
            : TimeEntry::query()->whereKey(0);

        return $table
            ->query($query)
            ->heading('Recent Time Entries')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->columns([
                Tables\Columns\TextColumn::make('work_date')
                    ->label('Work Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->limit(30),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->limit(35),

                Tables\Columns\TextColumn::make('working_minutes')
                    ->label('Hours')
                    ->formatStateUsing(
                        fn (int $state): string =>
                            number_format($state / 60, 2) . 'h'
                    ),

                Tables\Columns\TextColumn::make('entry_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => ucwords(
                            str_replace('_', ' ', $state->value)
                        )
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => ucwords(
                            str_replace('_', ' ', $state->value)
                        )
                    ),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->actions([
                ViewAction::make()
                    ->url(
                        fn (TimeEntry $record): string => route(
                            'filament.user.resources.time-entries.view',
                            ['record' => $record]
                        )
                    ),
            ])
            ->emptyStateHeading('No time entries')
            ->emptyStateDescription(
                'You have not recorded any time entries yet.'
            );
    }
}