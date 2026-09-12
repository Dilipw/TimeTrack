<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Services\PayrollService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPayroll extends ViewRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calculate')
                ->label('Calculate Payroll')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status->value === 'draft')
                ->action(function (): void {
                    app(PayrollService::class)->calculate($this->record);

                    $this->record->refresh();

                    Notification::make()
                        ->title('Payroll calculated')
                        ->body('Payroll amounts have been recalculated from approved time entries.')
                        ->success()
                        ->send();
                }),

            Action::make('finalize')
                ->label('Finalize Payroll')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Finalize Payroll')
                ->modalDescription(
                    'Once finalized, this payroll becomes immutable and its approved time entries and hourly rate are preserved as historical snapshots.'
                )
                ->visible(fn (): bool => $this->record->status->value === 'draft')
                ->action(function (): void {
                    $payroll = app(PayrollService::class)->finalize(
                        $this->record,
                        auth()->user(),
                    );

                    $this->record = $payroll;

                    Notification::make()
                        ->title('Payroll finalized')
                        ->body('The payroll has been finalized successfully.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
