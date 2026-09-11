<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use Database\Factories\TimesheetApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'time_entry_id',
    'approver_user_id',
    'action',
    'rejection_reason',
    'acted_at',
])]
class TimesheetApproval extends Model
{
    /** @use HasFactory<TimesheetApprovalFactory> */
    use HasFactory;

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    protected function casts(): array
    {
        return [
            'action' => ApprovalAction::class,
            'acted_at' => 'datetime',
        ];
    }
}