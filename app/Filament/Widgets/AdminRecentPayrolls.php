<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Admin;

use App\Enums\PayrollStatus;
use App\Services\DashboardService;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AdminRecentPayrolls extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Payrolls';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                app(DashboardService::class)
                    ->getRecentPayrollsQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('employee.employee_code')
                    ->label('Employee ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(
                        fn ($state, $record): string => sprintf(
                            '%s %s',
                            $record->employee->first_name,
                            $record->employee->last_name,
                        )
                    )
                    ->searchable(),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(
                        fn ($state, $record): string => sprintf(
                            '%s - %s',
                            $record->period_start->format('d M Y'),
                            $record->period_end->format('d M Y'),
                        )
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('regular_minutes')
                    ->label('Regular')
                    ->formatStateUsing(
                        fn (?int $state): string => $state === null
                            ? '-'
                            : sprintf(
                                '%dh %02dm',
                                intdiv($state, 60),
                                $state % 60,
                            )
                    ),

                Tables\Columns\TextColumn::make('overtime_minutes')
                    ->label('OT')
                    ->formatStateUsing(
                        fn (?int $state): string => $state === null
                            ? '-'
                            : sprintf(
                                '%dh %02dm',
                                intdiv($state, 60),
                                $state % 60,
                            )
                    ),

                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Gross')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (PayrollStatus $state): string =>
                            $state->name === 'FINALIZED'
                                ? 'Finalized'
                                : 'Draft'
                    )
                    ->color(
                        fn (PayrollStatus $state): string =>
                            $state === PayrollStatus::FINALIZED
                                ? 'success'
                                : 'warning'
                    ),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(
                        fn ($record): string => route(
                            'filament.user.resources.payrolls.view',
                            [
                                'record' => $record,
                            ],
                        )
                    ),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No payroll records')
            ->emptyStateDescription(
                'Payroll records will appear here once they are created.'
            )
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}