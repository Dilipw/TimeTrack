<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Admin;

use App\Services\TimeEntryApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AdminPendingApprovals extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pending Time Approvals';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                app(\App\Services\DashboardService::class)
                    ->getPendingApprovalsQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('employee.employee_code')
                    ->label('Employee ID')
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->limit(35),

                Tables\Columns\TextColumn::make('work_date')
                    ->label('Work Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('working_minutes')
                    ->label('Hours')
                    ->formatStateUsing(
                        fn (?int $state): string => $state === null
                            ? '-'
                            : sprintf(
                                '%dh %02dm',
                                intdiv($state, 60),
                                $state % 60,
                            )
                    ),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y h:i A')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(
                        fn ($record): string => route(
                            'filament.user.resources.time-entries.view',
                            [
                                'record' => $record,
                            ],
                        )
                    ),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Time Entry')
                    ->modalDescription(
                        'Are you sure you want to approve this time entry?'
                    )
                    ->modalSubmitActionLabel('Approve')
                    ->action(function ($record): void {
                        try {
                            app(TimeEntryApprovalService::class)->approve(
                                timeEntry: $record,
                                approver: auth()->user(),
                            );

                            Notification::make()
                                ->success()
                                ->title('Time entry approved')
                                ->body(
                                    'The time entry has been approved successfully.'
                                )
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Unable to approve time entry')
                                ->body(
                                    collect($exception->errors())
                                        ->flatten()
                                        ->implode(' ')
                                )
                                ->persistent()
                                ->send();
                        } catch (\Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Unable to approve time entry')
                                ->body(
                                    'Something went wrong while approving the time entry.'
                                )
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->minLength(1)
                            ->maxLength(2000)
                            ->rows(4)
                            ->placeholder(
                                'Explain why this time entry is being rejected...'
                            ),
                    ])
                    ->modalHeading('Reject Time Entry')
                    ->modalDescription(
                        'Please provide a reason for rejecting this time entry.'
                    )
                    ->modalSubmitActionLabel('Reject')
                    ->action(function (
                        $record,
                        array $data
                    ): void {
                        try {
                            app(TimeEntryApprovalService::class)->reject(
                                timeEntry: $record,
                                approver: auth()->user(),
                                reason: $data['reason'],
                            );

                            Notification::make()
                                ->success()
                                ->title('Time entry rejected')
                                ->body(
                                    'The employee can now edit and resubmit this entry.'
                                )
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Unable to reject time entry')
                                ->body(
                                    collect($exception->errors())
                                        ->flatten()
                                        ->implode(' ')
                                )
                                ->persistent()
                                ->send();
                        } catch (\Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Unable to reject time entry')
                                ->body(
                                    'Something went wrong while rejecting the time entry.'
                                )
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No pending approvals')
            ->emptyStateDescription(
                'All submitted time entries have been processed.'
            )
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}