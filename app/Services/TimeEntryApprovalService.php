<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Enums\TimeEntryStatus;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\TimesheetApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeEntryApprovalService
{
    /**
     * Approve a submitted time entry.
     */
    public function approve(
        TimeEntry $timeEntry,
        User $approver
    ): TimeEntry {
        return DB::transaction(function () use (
            $timeEntry,
            $approver
        ): TimeEntry {
            $timeEntry = $this->lockTimeEntry($timeEntry);

            $this->ensureSubmitted($timeEntry);
            $this->ensureCanApprove($timeEntry, $approver);

            $timeEntry->update([
                'status' => TimeEntryStatus::APPROVED,
                'approved_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            TimesheetApproval::query()->create([
                'time_entry_id' => $timeEntry->id,
                'approver_user_id' => $approver->id,
                'action' => ApprovalAction::APPROVED,
                'rejection_reason' => null,
                'acted_at' => now(),
            ]);

            return $timeEntry->refresh();
        });
    }

    /**
     * Reject a submitted time entry.
     */
    public function reject(
        TimeEntry $timeEntry,
        User $approver,
        string $reason
    ): TimeEntry {
        return DB::transaction(function () use (
            $timeEntry,
            $approver,
            $reason
        ): TimeEntry {
            $timeEntry = $this->lockTimeEntry($timeEntry);

            $this->ensureSubmitted($timeEntry);
            $this->ensureCanApprove($timeEntry, $approver);
            $this->validateRejectionReason($reason);

            $timeEntry->update([
                'status' => TimeEntryStatus::REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'approved_at' => null,
            ]);

            TimesheetApproval::query()->create([
                'time_entry_id' => $timeEntry->id,
                'approver_user_id' => $approver->id,
                'action' => ApprovalAction::REJECTED,
                'rejection_reason' => $reason,
                'acted_at' => now(),
            ]);

            return $timeEntry->refresh();
        });
    }

    private function lockTimeEntry(TimeEntry $timeEntry): TimeEntry
    {
        return TimeEntry::query()
            ->whereKey($timeEntry->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureSubmitted(TimeEntry $timeEntry): void
    {
        if ($timeEntry->status !== TimeEntryStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted time entries can be approved or rejected.',
            ]);
        }
    }

    private function ensureCanApprove(
        TimeEntry $timeEntry,
        User $approver
    ): void {
        if ($timeEntry->employee?->user_id === $approver->id) {
            throw ValidationException::withMessages([
                'approver' => 'Employees cannot approve their own time entries.',
            ]);
        }
    }

    private function validateRejectionReason(string $reason): void
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'A rejection reason is required.',
            ]);
        }

        if (mb_strlen(trim($reason)) > 2000) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'The rejection reason cannot exceed 2000 characters.',
            ]);
        }
    }
}