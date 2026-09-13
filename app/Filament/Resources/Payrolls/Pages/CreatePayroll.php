<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function handleRecordCreation(array $data): Payroll
    {
        try {
            $employee = Employee::query()->findOrFail(
                (int) $data['employee_id']
            );

            return app(PayrollService::class)->create(
                employee: $employee,
                periodStart: $data['period_start'],
                periodEnd: $data['period_end'],
                data: [
                    'overtime_multiplier' => $data['overtime_multiplier'],
                    'adjustment_amount' => $data['adjustment_amount'],
                    'deduction_amount' => $data['deduction_amount'],
                ],
            );
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError(
                        "data.{$field}",
                        $message,
                    );
                }
            }

            Notification::make()
                ->danger()
                ->title('Unable to create payroll')
                ->body(
                    collect($errors)
                        ->flatten()
                        ->implode(' ')
                )
                ->persistent()
                ->send();

            $this->halt();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('Unable to create payroll')
                ->body(
                    'Something went wrong while creating the payroll. Please try again.'
                )
                ->persistent()
                ->send();

            $this->halt();
        }

        throw new \LogicException(
            'Payroll creation halted unexpectedly.'
        );
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Payroll created')
            ->body('Draft payroll has been created successfully.')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return PayrollResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
