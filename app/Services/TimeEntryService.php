<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\TimeEntryStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeEntryService
{
    /**
     * Create a new draft time entry.
     */
    public function create(
        Employee $employee,
        Project $project,
        Task $task,
        array $data
    ): TimeEntry {
        return DB::transaction(function () use (
            $employee,
            $project,
            $task,
            $data
        ): TimeEntry {
            $employee = $this->lockEmployee($employee);

            $project = $this->freshProject($project);
            $task = $this->freshTask($task);

            $this->validateEmployee($employee);
            $this->validateProject($employee, $project);
            $this->validateTask($employee, $project, $task);

            $workDate = $this->parseWorkDate($data['work_date']);

            $this->validateWorkDate(
                $employee,
                $project,
                $workDate
            );

            [$startTime, $endTime] = $this->parseTimeRange(
                $data['start_time'],
                $data['end_time']
            );

            $breakMinutes = (int) ($data['break_minutes'] ?? 0);

            $workingMinutes = $this->calculateWorkingMinutes(
                $startTime,
                $endTime,
                $breakMinutes
            );

            $this->ensureNoOverlap(
                $employee,
                $workDate,
                $startTime,
                $endTime
            );

            return TimeEntry::query()->create([
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'task_id' => $task->id,
                'work_date' => $workDate,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'break_minutes' => $breakMinutes,
                'working_minutes' => $workingMinutes,
                'entry_type' => $data['entry_type'],
                'status' => TimeEntryStatus::DRAFT,
            ]);
        });
    }

    /**
     * Update a draft or rejected time entry.
     */
    public function update(
        TimeEntry $timeEntry,
        Employee $employee,
        Project $project,
        Task $task,
        array $data
    ): TimeEntry {
        return DB::transaction(function () use (
            $timeEntry,
            $employee,
            $project,
            $task,
            $data
        ): TimeEntry {
            $employee = $this->lockEmployee($employee);
            $timeEntry = $this->lockTimeEntry($timeEntry);

            $project = $this->freshProject($project);
            $task = $this->freshTask($task);

            $this->ensureEmployeeOwnsEntry(
                $timeEntry,
                $employee
            );

            $this->ensureEditable($timeEntry);

            $this->validateEmployee($employee);
            $this->validateProject($employee, $project);
            $this->validateTask($employee, $project, $task);

            $workDate = $this->parseWorkDate($data['work_date']);

            $this->validateWorkDate(
                $employee,
                $project,
                $workDate
            );

            [$startTime, $endTime] = $this->parseTimeRange(
                $data['start_time'],
                $data['end_time']
            );

            $breakMinutes = (int) ($data['break_minutes'] ?? 0);

            $workingMinutes = $this->calculateWorkingMinutes(
                $startTime,
                $endTime,
                $breakMinutes
            );

            $this->ensureNoOverlap(
                $employee,
                $workDate,
                $startTime,
                $endTime,
                $timeEntry->id
            );

            $timeEntry->update([
                'project_id' => $project->id,
                'task_id' => $task->id,
                'work_date' => $workDate,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'break_minutes' => $breakMinutes,
                'working_minutes' => $workingMinutes,
                'entry_type' => $data['entry_type'],
            ]);

            return $timeEntry->refresh();
        });
    }

    /**
     * Submit a draft or rejected entry for approval.
     *
     * Working minutes are always recalculated server-side before
     * the entry becomes eligible for approval.
     */
    public function submit(
        TimeEntry $timeEntry,
        Employee $employee
    ): TimeEntry {
        return DB::transaction(function () use (
            $timeEntry,
            $employee
        ): TimeEntry {
            $employee = $this->lockEmployee($employee);
            $timeEntry = $this->lockTimeEntry($timeEntry);

            $this->ensureEmployeeOwnsEntry(
                $timeEntry,
                $employee
            );

            if (! in_array(
                $timeEntry->status,
                [
                    TimeEntryStatus::DRAFT,
                    TimeEntryStatus::REJECTED,
                ],
                true
            )) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft or rejected time entries can be submitted.',
                ]);
            }

            $this->validateEmployee($employee);

            $project = $this->freshProject(
                $timeEntry->project
            );

            $task = $this->freshTask(
                $timeEntry->task
            );

            $this->validateProject(
                $employee,
                $project
            );

            $this->validateTask(
                $employee,
                $project,
                $task
            );

            $this->validateWorkDate(
                $employee,
                $project,
                $timeEntry->work_date
            );

            /*
             * TimeEntry casts start_time/end_time to Carbon instances.
             *
             * Do not cast them to string here. parseTime() handles both
             * Carbon instances and string values safely.
             */
            $startTime = $this->parseTime(
                $timeEntry->start_time
            );

            $endTime = $this->parseTime(
                $timeEntry->end_time
            );

            $workingMinutes = $this->calculateWorkingMinutes(
                $startTime,
                $endTime,
                (int) $timeEntry->break_minutes
            );

            $this->ensureNoOverlap(
                $employee,
                $timeEntry->work_date,
                $startTime,
                $endTime,
                $timeEntry->id
            );

            $timeEntry->update([
                'working_minutes' => $workingMinutes,
                'status' => TimeEntryStatus::SUBMITTED,
                'submitted_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            return $timeEntry->refresh();
        });
    }

    /**
     * Cancel a draft time entry.
     */
    public function cancel(
        TimeEntry $timeEntry,
        Employee $employee
    ): TimeEntry {
        return DB::transaction(function () use (
            $timeEntry,
            $employee
        ): TimeEntry {
            $employee = $this->lockEmployee($employee);
            $timeEntry = $this->lockTimeEntry($timeEntry);

            $this->ensureEmployeeOwnsEntry(
                $timeEntry,
                $employee
            );

            if ($timeEntry->status !== TimeEntryStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft time entries can be cancelled.',
                ]);
            }

            $timeEntry->update([
                'status' => TimeEntryStatus::CANCELLED,
            ]);

            return $timeEntry->refresh();
        });
    }

    /**
     * Lock and return a fresh employee record.
     */
    private function lockEmployee(Employee $employee): Employee
    {
        return Employee::query()
            ->whereKey($employee->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Lock and return a fresh time entry record.
     */
    private function lockTimeEntry(TimeEntry $timeEntry): TimeEntry
    {
        return TimeEntry::query()
            ->whereKey($timeEntry->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Return a fresh project instance.
     */
    private function freshProject(Project $project): Project
    {
        return Project::query()
            ->findOrFail($project->id);
    }

    /**
     * Return a fresh task instance.
     */
    private function freshTask(Task $task): Task
    {
        return Task::query()
            ->findOrFail($task->id);
    }

    /**
     * Ensure the employee is active.
     */
    private function validateEmployee(Employee $employee): void
    {
        if ($employee->status !== EmployeeStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'employee' => 'Inactive employees cannot create time entries.',
            ]);
        }
    }

    /**
     * Ensure the employee can log time against the project.
     */
    private function validateProject(
        Employee $employee,
        Project $project
    ): void {
        if ($project->status !== ProjectStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'project' => 'Time can only be logged against an active project.',
            ]);
        }

        $isMember = $project
            ->activeMembers()
            ->whereKey($employee->id)
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'project' => 'Employee is not an active member of this project.',
            ]);
        }
    }

    /**
     * Ensure the task belongs to the project and the employee
     * is an active assignee.
     */
    private function validateTask(
        Employee $employee,
        Project $project,
        Task $task
    ): void {
        if ($task->project_id !== $project->id) {
            throw ValidationException::withMessages([
                'task' => 'The selected task does not belong to this project.',
            ]);
        }

        if (in_array(
            $task->status,
            [
                TaskStatus::COMPLETED,
                TaskStatus::CANCELLED,
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'task' => 'Time cannot be logged against a completed or cancelled task.',
            ]);
        }

        $isAssignee = $task
            ->activeAssignees()
            ->whereKey($employee->id)
            ->exists();

        if (! $isAssignee) {
            throw ValidationException::withMessages([
                'task' => 'Employee is not an active assignee of this task.',
            ]);
        }
    }

    /**
     * Validate whether the work date is allowed.
     */
    private function validateWorkDate(
        Employee $employee,
        Project $project,
        Carbon $workDate
    ): void {
        $today = now()->startOfDay();

        if ($workDate->greaterThan($today)) {
            throw ValidationException::withMessages([
                'work_date' => 'Work date cannot be in the future.',
            ]);
        }

        if ($workDate->lessThan(
            $employee->joining_date->startOfDay()
        )) {
            throw ValidationException::withMessages([
                'work_date' => 'Work date cannot be before the employee joining date.',
            ]);
        }

        if ($workDate->lessThan(
            $project->start_date->startOfDay()
        )) {
            throw ValidationException::withMessages([
                'work_date' => 'Work date cannot be before the project start date.',
            ]);
        }

        if (
            $project->end_date !== null
            && $workDate->greaterThan(
                $project->end_date->startOfDay()
            )
        ) {
            throw ValidationException::withMessages([
                'work_date' => 'Work date cannot be after the project end date.',
            ]);
        }
    }

    /**
     * Parse and normalize a work date.
     */
    private function parseWorkDate(
        string|Carbon $value
    ): Carbon {
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'work_date' => 'Invalid work date.',
            ]);
        }
    }

    /**
     * Parse and validate a time range.
     *
     * Both form strings and Eloquent Carbon values are supported.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parseTimeRange(
        string|Carbon $startTime,
        string|Carbon $endTime
    ): array {
        try {
            $start = $this->parseTime($startTime);
            $end = $this->parseTime($endTime);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'start_time' => 'Invalid start time.',
                'end_time' => 'Invalid end time.',
            ]);
        }

        if (! $start->lt($end)) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        return [$start, $end];
    }

    /**
     * Parse a time value into a normalized Carbon instance.
     *
     * Supported values:
     * - Carbon
     * - H:i
     * - H:i:s
     * - h:i A
     * - h:i a
     * - g:i A
     * - g:i a
     */
    private function parseTime(
        string|Carbon $time
    ): Carbon {
        if ($time instanceof Carbon) {
            return Carbon::create(
                2000,
                1,
                1,
                $time->hour,
                $time->minute,
                $time->second
            );
        }

        $time = trim($time);

        foreach ([
            'H:i:s',
            'H:i',
            'h:i A',
            'h:i a',
            'g:i A',
            'g:i a',
        ] as $format) {
            try {
                $parsed = Carbon::createFromFormat(
                    $format,
                    $time
                );

                if ($parsed !== false) {
                    return $parsed->setDate(
                        2000,
                        1,
                        1
                    );
                }
            } catch (\Throwable) {
                continue;
            }
        }

        throw new \InvalidArgumentException(
            'Invalid time format.'
        );
    }

    /**
     * Calculate payable working minutes.
     */
    private function calculateWorkingMinutes(
        Carbon $start,
        Carbon $end,
        int $breakMinutes
    ): int {
        if ($breakMinutes < 0) {
            throw ValidationException::withMessages([
                'break_minutes' => 'Break duration cannot be negative.',
            ]);
        }

        $grossMinutes = $start->diffInMinutes($end);

        if ($breakMinutes >= $grossMinutes) {
            throw ValidationException::withMessages([
                'break_minutes' => 'Break duration must be less than the total work duration.',
            ]);
        }

        $workingMinutes = $grossMinutes - $breakMinutes;

        if ($workingMinutes <= 0) {
            throw ValidationException::withMessages([
                'break_minutes' => 'Working duration must be greater than zero.',
            ]);
        }

        return $workingMinutes;
    }

    /**
     * Prevent overlapping time entries for the same employee/date.
     *
     * Draft, submitted and approved entries reserve their time period.
     * Rejected and cancelled entries do not participate in overlap checks.
     */
    private function ensureNoOverlap(
        Employee $employee,
        Carbon $workDate,
        Carbon $startTime,
        Carbon $endTime,
        ?int $ignoreId = null
    ): void {
        $query = TimeEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->whereIn('status', [
                TimeEntryStatus::DRAFT,
                TimeEntryStatus::SUBMITTED,
                TimeEntryStatus::APPROVED,
            ])
            ->where(
                'start_time',
                '<',
                $endTime->format('H:i:s')
            )
            ->where(
                'end_time',
                '>',
                $startTime->format('H:i:s')
            );

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_time' => 'This time period overlaps with another time entry for the employee.',
            ]);
        }
    }

    /**
     * Ensure the entry is editable.
     */
    private function ensureEditable(
        TimeEntry $timeEntry
    ): void {
        if (! in_array(
            $timeEntry->status,
            [
                TimeEntryStatus::DRAFT,
                TimeEntryStatus::REJECTED,
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or rejected time entries can be edited.',
            ]);
        }
    }

    /**
     * Ensure the employee owns the time entry.
     */
    private function ensureEmployeeOwnsEntry(
        TimeEntry $timeEntry,
        Employee $employee
    ): void {
        if ($timeEntry->employee_id !== $employee->id) {
            throw ValidationException::withMessages([
                'employee' => 'You are not authorized to modify this time entry.',
            ]);
        }
    }
}
