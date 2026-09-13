<?php

declare(strict_types=1);

namespace App\Filament\Resources\TimeEntries\Tables;

use App\Enums\TimeEntryStatus;
use App\Enums\TimeEntryType;
use App\Models\TimeEntry;
use App\Services\TimeEntryApprovalService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Throwable;

class TimeEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('Sr. No.')
                    ->rowIndex()
                    ->alignCenter()
                    ->width('70px'),

                TextColumn::make('employee.employee_code')
                    ->label('Employee')
                    ->formatStateUsing(
                        function (?string $state, TimeEntry $record): string {
                            $employee = $record->employee;

                            if (! $employee) {
                                return '-';
                            }

                            return "{$employee->employee_code} - {$employee->first_name} {$employee->last_name}";
                        }
                    )
                    ->searchable([
                        'employee_code',
                        'first_name',
                        'last_name',
                    ])
                    ->sortable()
                    ->wrap()
                    ->limit(35),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(30),

                TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(40),

                TextColumn::make('work_date')
                    ->label('Work Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('work_time')
                    ->label('Time')
                    ->state(
                        function (TimeEntry $record): string {
                            if (! $record->start_time || ! $record->end_time) {
                                return '-';
                            }

                            return sprintf(
                                '%s - %s',
                                Carbon::parse($record->start_time)->format('h:i A'),
                                Carbon::parse($record->end_time)->format('h:i A'),
                            );
                        }
                    )
                    ->sortable(false),

                TextColumn::make('break_minutes')
                    ->label('Break')
                    ->formatStateUsing(
                        fn (?int $state): string => self::formatDuration($state)
                    )
                    ->sortable(),

                TextColumn::make('working_minutes')
                    ->label('Working Time')
                    ->formatStateUsing(
                        fn (?int $state): string => self::formatDuration($state)
                    )
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('entry_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn (TimeEntryType $state): string => match ($state) {
                            TimeEntryType::REGULAR => 'Regular',
                            TimeEntryType::OVERTIME => 'Overtime',
                        }
                    )
                    ->color(
                        fn (TimeEntryType $state): string => match ($state) {
                            TimeEntryType::REGULAR => 'gray',
                            TimeEntryType::OVERTIME => 'warning',
                        }
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (TimeEntryStatus $state): string => match ($state) {
                            TimeEntryStatus::DRAFT => 'Draft',
                            TimeEntryStatus::SUBMITTED => 'Submitted',
                            TimeEntryStatus::APPROVED => 'Approved',
                            TimeEntryStatus::REJECTED => 'Rejected',
                            TimeEntryStatus::CANCELLED => 'Cancelled',
                        }
                    )
                    ->color(
                        fn (TimeEntryStatus $state): string => match ($state) {
                            TimeEntryStatus::DRAFT => 'gray',
                            TimeEntryStatus::SUBMITTED => 'info',
                            TimeEntryStatus::APPROVED => 'success',
                            TimeEntryStatus::REJECTED => 'danger',
                            TimeEntryStatus::CANCELLED => 'warning',
                        }
                    )
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rejected_at')
                    ->label('Rejected')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Time Entry')
                    ->modalDescription(
                        'Are you sure you want to approve this time entry? Approved hours will become eligible for payroll.'
                    )
                    ->modalSubmitActionLabel('Approve')
                    ->visible(
                        fn (TimeEntry $record): bool =>
                            $record->status === TimeEntryStatus::SUBMITTED
                            && auth()->user()->can('approve', $record)
                    )
                    ->action(function (TimeEntry $record): void {
                        try {
                            $approvedEntry = app(
                                TimeEntryApprovalService::class
                            )->approve(
                                timeEntry: $record,
                                approver: auth()->user(),
                            );

                            Notification::make()
                                ->success()
                                ->title('Time entry approved')
                                ->body(
                                    'The time entry has been approved. '
                                    . 'Working time: '
                                    . self::formatDuration($approvedEntry->working_minutes)
                                    . '.'
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
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Unable to approve time entry')
                                ->body(
                                    'An unexpected error occurred while approving the time entry.'
                                )
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Time Entry')
                    ->modalDescription(
                        'Provide a reason for rejecting this time entry.'
                    )
                    ->modalSubmitActionLabel('Reject')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->minLength(3)
                            ->maxLength(2000)
                            ->rows(4)
                            ->placeholder(
                                'Explain why this time entry is being rejected...'
                            ),
                    ])
                    ->visible(
                        fn (TimeEntry $record): bool =>
                            $record->status === TimeEntryStatus::SUBMITTED
                            && auth()->user()->can('reject', $record)
                    )
                    ->action(function (
                        TimeEntry $record,
                        array $data
                    ): void {
                        try {
                            app(TimeEntryApprovalService::class)->reject(
                                timeEntry: $record,
                                approver: auth()->user(),
                                rejectionReason: $data['rejection_reason'],
                            );

                            Notification::make()
                                ->success()
                                ->title('Time entry rejected')
                                ->body(
                                    'The time entry has been rejected and can be corrected and resubmitted.'
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
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Unable to reject time entry')
                                ->body(
                                    'An unexpected error occurred while rejecting the time entry.'
                                )
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('work_date', 'desc');
    }

    private static function formatDuration(?int $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        if ($minutes === 0) {
            return '0m';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return match (true) {
            $hours > 0 && $remainingMinutes > 0 => "{$hours}h {$remainingMinutes}m",
            $hours > 0 => "{$hours}h",
            default => "{$remainingMinutes}m",
        };
    }
}