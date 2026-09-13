<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Pages;

use App\Enums\PayrollStatus;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Payroll;
use App\Services\PayrollService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        if (! $record instanceof Payroll) {
            throw new \LogicException(
                'Expected a Payroll record.'
            );
        }

        if ($record->status !== PayrollStatus::DRAFT) {
            Notification::make()
                ->danger()
                ->title('Payroll cannot be edited')
                ->body('Finalized payroll cannot be modified.')
                ->send();

            $this->halt();
        }

        $record->update([
            'employee_id' => $data['employee_id'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'overtime_multiplier' => $data['overtime_multiplier'],
            'adjustment_amount' => $data['adjustment_amount'],
            'deduction_amount' => $data['deduction_amount'],
        ]);

        return $record->refresh();
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            Action::make('calculate')
                ->label('Calculate')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Calculate Payroll')
                ->modalDescription(
                    'Calculate payroll using approved time entries for this period.'
                )
                ->modalSubmitActionLabel('Calculate')
                ->visible(
                    fn (Payroll $record): bool =>
                        $record->status === PayrollStatus::DRAFT
                )
                ->action(function (Payroll $record): void {
                    try {
                        $payroll = app(PayrollService::class)
                            ->calculate($record);

                        Notification::make()
                            ->success()
                            ->title('Payroll calculated')
                            ->body(
                                "Regular: {$payroll->regular_minutes} min | "
                                . "OT: {$payroll->overtime_minutes} min | "
                                . "Net: ₹{$payroll->net_amount}"
                            )
                            ->send();

                        $this->refreshFormData([
                            'hourly_rate',
                            'regular_minutes',
                            'overtime_minutes',
                            'regular_amount',
                            'overtime_amount',
                            'gross_amount',
                            'net_amount',
                        ]);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Unable to calculate payroll')
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
                            ->title('Unable to calculate payroll')
                            ->body(
                                'Something went wrong while calculating payroll.'
                            )
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('finalize')
                ->label('Finalize')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Finalize Payroll')
                ->modalDescription(
                    'Finalizing payroll will permanently snapshot the payroll calculation. This action cannot be undone.'
                )
                ->modalSubmitActionLabel('Finalize Payroll')
                ->visible(
                    fn (Payroll $record): bool =>
                        $record->status === PayrollStatus::DRAFT
                )
                ->action(function (Payroll $record): void {
                    try {
                        $payroll = app(PayrollService::class)
                            ->finalize(
                                payroll: $record,
                                user: auth()->user(),
                            );

                        Notification::make()
                            ->success()
                            ->title('Payroll finalized')
                            ->body(
                                "Payroll finalized successfully. "
                                . "Net amount: ₹{$payroll->net_amount}"
                            )
                            ->send();

                        $this->redirect(
                            PayrollResource::getUrl('view', [
                                'record' => $payroll->getKey(),
                            ])
                        );
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Unable to finalize payroll')
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
                            ->title('Unable to finalize payroll')
                            ->body(
                                'Something went wrong while finalizing payroll.'
                            )
                            ->persistent()
                            ->send();
                    }
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}