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
        Log::info('TIME ENTRY CREATE: handleRecordCreation started', [
            'user_id' => auth()->id(),
            'data' => $data,
        ]);

        try {
            Log::info('TIME ENTRY CREATE: resolving employee', [
                'employee_id' => $data['employee_id'] ?? null,
            ]);

            $employee = Employee::query()->findOrFail(
                (int) $data['employee_id']
            );

            Log::info('TIME ENTRY CREATE: employee resolved', [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'status' => $employee->status->value,
            ]);

            Log::info('TIME ENTRY CREATE: resolving project', [
                'project_id' => $data['project_id'] ?? null,
            ]);

            $project = Project::query()->findOrFail(
                (int) $data['project_id']
            );

            Log::info('TIME ENTRY CREATE: project resolved', [
                'project_id' => $project->id,
                'project_code' => $project->project_code,
                'status' => $project->status->value,
            ]);

            Log::info('TIME ENTRY CREATE: resolving task', [
                'task_id' => $data['task_id'] ?? null,
            ]);

            $task = Task::query()->findOrFail(
                (int) $data['task_id']
            );

            Log::info('TIME ENTRY CREATE: task resolved', [
                'task_id' => $task->id,
                'title' => $task->title,
                'status' => $task->status->value,
            ]);

            Log::info('TIME ENTRY CREATE: calling TimeEntryService::create');

            $timeEntry = app(TimeEntryService::class)->create(
                employee: $employee,
                project: $project,
                task: $task,
                data: $data,
            );

            Log::info('TIME ENTRY CREATE: TimeEntryService::create completed', [
                'time_entry_id' => $timeEntry->id,
                'working_minutes' => $timeEntry->working_minutes,
                'status' => $timeEntry->status->value,
            ]);

            return $timeEntry;

        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            Log::warning('TIME ENTRY CREATE: validation exception', [
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
                'errors' => $errors,
            ]);

            $fieldMapping = [
                'employee' => 'employee_id',
                'project' => 'project_id',
                'task' => 'task_id',
            ];

            foreach ($errors as $field => $messages) {
                $formField = $fieldMapping[$field] ?? $field;

                foreach ($messages as $message) {
                    /*
                     * Filament's resource form state is stored under
                     * the "data" property.
                     *
                     * Therefore:
                     *
                     * task_id
                     *
                     * becomes:
                     *
                     * data.task_id
                     */
                    $this->addError(
                        "data.{$formField}",
                        $message,
                    );

                    Log::info(
                        'TIME ENTRY CREATE: form error added',
                        [
                            'field' => "data.{$formField}",
                            'message' => $message,
                        ]
                    );
                }
            }

            Notification::make()
                ->danger()
                ->title('Unable to create time entry')
                ->body(
                    collect($errors)
                        ->flatten()
                        ->implode(' ')
                )
                ->persistent()
                ->send();

            /*
             * Stop Filament's create lifecycle.
             *
             * This keeps the user on the form instead of allowing
             * the create action to continue.
             */
            $this->halt();
        } catch (Throwable $exception) {
            Log::error('TIME ENTRY CREATE: unexpected exception', [
                'user_id' => auth()->id(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            report($exception);

            Notification::make()
                ->danger()
                ->title('Unable to create time entry')
                ->body(
                    'Something went wrong while creating the time entry. Please try again.'
                )
                ->persistent()
                ->send();

            $this->halt();
        }
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