<?php

declare(strict_types=1);

namespace App\Filament\Resources\TimeEntries\Pages;

use App\Filament\Resources\TimeEntries\TimeEntryResource;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\TimeEntryService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateTimeEntry extends CreateRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function handleRecordCreation(array $data): TimeEntry
    {
        try {
            $employee = Employee::query()->findOrFail(
                (int) $data['employee_id'],
            );

            $project = Project::query()->findOrFail(
                (int) $data['project_id'],
            );

            $task = Task::query()->findOrFail(
                (int) $data['task_id'],
            );

            $service = app(TimeEntryService::class);

            // Create the entry first.
            $timeEntry = $service->create(
                employee: $employee,
                project: $project,
                task: $task,
                data: $data,
            );

            // Newly created employee time entries are
            // immediately submitted for approval.
            return $service->submit(
                timeEntry: $timeEntry,
                employee: $employee,
            );
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            $fieldMapping = [
                'employee' => 'employee_id',
                'project' => 'project_id',
                'task' => 'task_id',
            ];

            foreach ($errors as $field => $messages) {
                $formField = $fieldMapping[$field] ?? $field;

                foreach ($messages as $message) {
                    $this->addError(
                        "data.{$formField}",
                        $message,
                    );
                }
            }

            Notification::make()
                ->danger()
                ->title('Unable to create time entry')
                ->body(
                    collect($errors)
                        ->flatten()
                        ->implode(' '),
                )
                ->persistent()
                ->send();

            $this->halt();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('Unable to create time entry')
                ->body(
                    'Something went wrong while creating the time entry.',
                )
                ->persistent()
                ->send();

            $this->halt();
        }

        throw new \LogicException(
            'Time entry creation did not return a record.',
        );
    }

    protected function afterCreate(): void
    {
        Log::info('TIME ENTRY CREATE: afterCreate started', [
            'user_id' => auth()->id(),
            'record_id' => $this->record?->id,
        ]);

        Notification::make()
            ->success()
            ->title('Time entry created')
            ->body('The time entry has been saved successfully.')
            ->send();

        Log::info('TIME ENTRY CREATE: success notification sent', [
            'time_entry_id' => $this->record?->id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        $url = TimeEntryResource::getUrl('index');

        Log::info('TIME ENTRY CREATE: redirect URL generated', [
            'user_id' => auth()->id(),
            'time_entry_id' => $this->record?->id,
            'redirect_url' => $url,
        ]);

        return $url;
    }
}
