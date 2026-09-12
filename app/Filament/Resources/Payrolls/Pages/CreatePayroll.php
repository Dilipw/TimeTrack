<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Employee;
use App\Services\PayrollService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $employee = Employee::query()->findOrFail($data['employee_id']);

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
    }

    protected function getRedirectUrl(): string
    {
        return PayrollResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
