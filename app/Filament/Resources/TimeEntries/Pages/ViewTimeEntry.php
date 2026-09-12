<?php

namespace App\Filament\Resources\TimeEntries\Pages;

use App\Enums\TimeEntryStatus;
use App\Filament\Resources\TimeEntries\TimeEntryResource;
use App\Models\TimeEntry;
use App\Services\TimeEntryApprovalService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTimeEntry extends ViewRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(
                    fn (TimeEntry $record): bool => in_array(
                        $record->status,
                        [
                            TimeEntryStatus::DRAFT,
                            TimeEntryStatus::REJECTED,
                        ],
                        true
                    )
                ),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Time Entry')
                ->modalDescription(
                    'Are you sure you want to approve this time entry? Approved hours will become eligible for payroll.'
                )
                ->visible(
                    fn (TimeEntry $record): bool =>
                        $record->status === TimeEntryStatus::SUBMITTED
                        && auth()->user()->can('approve', $record)
                )
                ->action(function (TimeEntry $record): void {
                    app(TimeEntryApprovalService::class)->approve(
                        $record,
                        auth()->user()
                    );

                    Notification::make()
                        ->title('Time entry approved')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'approved_at',
                        'rejected_at',
                        'rejection_reason',
                    ]);
                }),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->maxLength(2000)
                        ->rows(4)
                        ->placeholder('Explain why this time entry is being rejected.'),
                ])
                ->modalHeading('Reject Time Entry')
                ->modalSubmitActionLabel('Reject Time Entry')
                ->visible(
                    fn (TimeEntry $record): bool =>
                        $record->status === TimeEntryStatus::SUBMITTED
                        && auth()->user()->can('reject', $record)
                )
                ->action(function (TimeEntry $record, array $data): void {
                    app(TimeEntryApprovalService::class)->reject(
                        $record,
                        auth()->user(),
                        $data['rejection_reason']
                    );

                    Notification::make()
                        ->title('Time entry rejected')
                        ->danger()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'approved_at',
                        'rejected_at',
                        'rejection_reason',
                    ]);
                }),
        ];
    }
}