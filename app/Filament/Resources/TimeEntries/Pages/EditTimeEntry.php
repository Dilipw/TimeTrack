<?php

namespace App\Filament\Resources\TimeEntries\Pages;

use App\Enums\TimeEntryStatus;
use App\Filament\Resources\TimeEntries\TimeEntryResource;
use App\Models\TimeEntry;
use App\Services\TimeEntryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditTimeEntry extends EditRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            Action::make('resubmit')
                ->label('Resubmit')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Resubmit Time Entry')
                ->modalDescription(
                    'Are you sure you want to resubmit this rejected time entry for approval?'
                )
                ->modalSubmitActionLabel('Confirm')
                ->visible(
                    fn (TimeEntry $record): bool =>
                        $record->status === TimeEntryStatus::REJECTED
                        && auth()->user()->can('submit', $record)
                )
                ->action(function (TimeEntry $record): void {
                    try {
                        /*
                         * Reload the record before submitting so we work
                         * with the latest database state.
                         */
                        $record = $record->fresh([
                            'employee',
                            'project',
                            'task',
                        ]);

                        if (! $record) {
                            throw ValidationException::withMessages([
                                'time_entry' => 'Time entry could not be found.',
                            ]);
                        }

                        if (
                            $record->status !== TimeEntryStatus::REJECTED
                        ) {
                            throw ValidationException::withMessages([
                                'status' => 'Only rejected time entries can be resubmitted.',
                            ]);
                        }

                        if (! $record->employee) {
                            throw ValidationException::withMessages([
                                'employee' => 'The employee associated with this time entry could not be found.',
                            ]);
                        }

                        /*
                         * The service intentionally receives the TimeEntry
                         * first and the owning Employee second.
                         */
                        $updatedEntry = app(TimeEntryService::class)->submit(
                            $record,
                            $record->employee
                        );

                        Notification::make()
                            ->title('Time entry resubmitted')
                            ->body(
                                "The time entry has been submitted for approval. "
                                . "Working time: {$updatedEntry->working_minutes} minutes."
                            )
                            ->success()
                            ->send();

                        $this->redirect(
                            TimeEntryResource::getUrl(
                                'view',
                                [
                                    'record' => $updatedEntry->getKey(),
                                ]
                            )
                        );
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->title('Unable to resubmit time entry')
                            ->body(
                                collect($exception->errors())
                                    ->flatten()
                                    ->implode(' ')
                            )
                            ->danger()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Unable to resubmit time entry')
                            ->body(
                                'An unexpected error occurred while resubmitting the time entry.'
                            )
                            ->danger()
                            ->send();
                    }
                }),

            DeleteAction::make(),

            ForceDeleteAction::make(),

            RestoreAction::make(),
        ];
    }
}
