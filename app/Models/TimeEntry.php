<?php

namespace App\Models;

use App\Enums\TimeEntryStatus;
use App\Enums\TimeEntryType;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;


#[Fillable([
    'employee_id',
    'project_id',
    'task_id',
    'work_date',
    'start_time',
    'end_time',
    'break_minutes',
    'working_minutes',
    'entry_type',
    'status',
    'submitted_at',
    'approved_at',
    'rejected_at',
    'rejection_reason',
])]
class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory, SoftDeletes;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'break_minutes' => 'integer',
            'working_minutes' => 'integer',
            'entry_type' => TimeEntryType::class,
            'status' => TimeEntryStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(TimesheetApproval::class)
            ->orderBy('acted_at');
    }
}
