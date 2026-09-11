<?php

namespace Database\Factories;

use App\Enums\ApprovalAction;
use App\Models\TimesheetApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimesheetApprovalFactory extends Factory
{
    protected $model = TimesheetApproval::class;

    public function definition(): array
    {
        return [
            'time_entry_id' => null,
            'approver_user_id' => null,
            'action' => ApprovalAction::APPROVED,
            'rejection_reason' => null,
            'acted_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'action' => ApprovalAction::APPROVED,
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'action' => ApprovalAction::REJECTED,
            'rejection_reason' => 'Please correct the recorded working hours.',
        ]);
    }
}