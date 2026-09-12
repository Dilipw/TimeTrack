<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'employee_code',
    'user_id',
    'first_name',
    'last_name',
    'department_id',
    'designation_id',
    'manager_id',
    'joining_date',
    'hourly_rate',
    'status',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'manager_id'
        );
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'manager_id'
        );
    }

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'hourly_rate' => 'decimal:2',
            'status' => EmployeeStatus::class,
        ];
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot([
                'assigned_at',
                'removed_at',
            ])
            ->withTimestamps();
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_assignees'
        )->withPivot([
            'assigned_at',
            'removed_at',
        ])->withTimestamps();
    }

    public function activeAssignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_assignees'
        )
            ->wherePivotNull('removed_at')
            ->withPivot([
                'assigned_at',
                'removed_at',
            ])
            ->withTimestamps();
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
