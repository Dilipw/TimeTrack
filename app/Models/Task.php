<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'project_id',
    'parent_task_id',
    'title',
    'description',
    'priority',
    'status',
    'due_date',
    'estimated_minutes',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Task::class,
            'parent_task_id'
        );
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(
            Task::class,
            'parent_task_id'
        );
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(
            Employee::class,
            'task_assignees'
        )->withPivot([
            'assigned_at',
            'removed_at',
        ])->withTimestamps();
    }

    public function activeAssignees(): BelongsToMany
    {
        return $this->belongsToMany(
            Employee::class,
            'task_assignees'
        )
            ->wherePivotNull('removed_at')
            ->withPivot([
                'assigned_at',
                'removed_at',
            ])
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
        ];
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }
}
