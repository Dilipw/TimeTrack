<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskService
{
    /**
     * Create a task and assign its active project members.
     *
     * @param  array{
     *     project_id:int,
     *     parent_task_id?:int|null,
     *     title:string,
     *     description?:string|null,
     *     priority:string|\BackedEnum,
     *     status:string|\BackedEnum,
     *     due_date?:string|\Carbon\CarbonInterface|null,
     *     estimated_minutes?:int|null,
     *     assignees?:array<int>
     *  } $data
     */
    public function create(User $actor, array $data): Task
    {
        return DB::transaction(function () use ($actor, $data): Task {
            $project = Project::query()
                ->lockForUpdate()
                ->findOrFail($data['project_id']);

            $this->authorizeProject($actor, $project);

            $this->validateProject($project);
            $this->validateParentTask($project, $data['parent_task_id'] ?? null);

            $assigneeIds = $this->validateAssignees(
                $project,
                $data['assignees'] ?? []
            );

            $task = Task::query()->create([
                'project_id' => $project->id,
                'parent_task_id' => $data['parent_task_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'],
                'status' => $data['status'],
                'due_date' => $data['due_date'] ?? null,
                'estimated_minutes' => $data['estimated_minutes'] ?? null,
            ]);

            $this->syncAssignees($task, $assigneeIds);

            return $task->fresh([
                'project',
                'parent',
                'activeAssignees',
            ]);
        });
    }

    /**
     * Update a task and preserve assignment history.
     *
     * @param array<string, mixed> $data
     */
    public function update(User $actor, Task $task, array $data): Task
    {
        return DB::transaction(function () use ($actor, $task, $data): Task {
            $task = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);

            $projectId = (int) ($data['project_id'] ?? $task->project_id);

            $project = Project::query()
                ->lockForUpdate()
                ->findOrFail($projectId);

            $this->authorizeProject($actor, $project);

            $this->validateProject($project);

            if ($task->status === TaskStatus::COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => 'Completed tasks cannot be modified.',
                ]);
            }

            if ($task->status === TaskStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => 'Cancelled tasks cannot be modified.',
                ]);
            }

            $parentTaskId = $data['parent_task_id'] ?? null;

            $this->validateParentTask($project, $parentTaskId, $task->id);

            $assigneeIds = $this->validateAssignees(
                $project,
                $data['assignees'] ?? []
            );

            $task->update([
                'project_id' => $project->id,
                'parent_task_id' => $parentTaskId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'],
                'status' => $data['status'],
                'due_date' => $data['due_date'] ?? null,
                'estimated_minutes' => $data['estimated_minutes'] ?? null,
            ]);

            $this->syncAssignees($task, $assigneeIds);

            return $task->fresh([
                'project',
                'parent',
                'activeAssignees',
            ]);
        });
    }

    private function authorizeProject(User $actor, Project $project): void
    {
        if ($actor->hasAnyRole(['super_admin', 'admin'])) {
            return;
        }

        if (! $actor->hasRole('project_manager')) {
            throw ValidationException::withMessages([
                'project_id' => 'You are not authorized to manage tasks.',
            ]);
        }

        $employee = $actor->employee;

        if (! $employee || $project->project_manager_id !== $employee->id) {
            throw ValidationException::withMessages([
                'project_id' => 'You can only manage tasks for projects assigned to you.',
            ]);
        }
    }

    private function validateProject(Project $project): void
    {
        if ($project->status === ProjectStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'project_id' => 'Tasks cannot be created or modified in a completed project.',
            ]);
        }

        if ($project->status === ProjectStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'project_id' => 'Tasks cannot be created or modified in a cancelled project.',
            ]);
        }
    }

    private function validateParentTask(
        Project $project,
        ?int $parentTaskId,
        ?int $currentTaskId = null,
    ): void {
        if ($parentTaskId === null) {
            return;
        }

        if ($currentTaskId !== null && $parentTaskId === $currentTaskId) {
            throw ValidationException::withMessages([
                'parent_task_id' => 'A task cannot be its own parent.',
            ]);
        }

        $parentTask = Task::query()
            ->whereKey($parentTaskId)
            ->where('project_id', $project->id)
            ->first();

        if (! $parentTask) {
            throw ValidationException::withMessages([
                'parent_task_id' => 'The selected parent task does not belong to this project.',
            ]);
        }

        if (in_array(
            $parentTask->status,
            [TaskStatus::COMPLETED, TaskStatus::CANCELLED],
            true
        )) {
            throw ValidationException::withMessages([
                'parent_task_id' => 'Completed or cancelled tasks cannot be selected as a parent task.',
            ]);
        }
    }

    /**
     * @param array<int> $assigneeIds
     * @return array<int>
     */
    private function validateAssignees(Project $project, array $assigneeIds): array
    {
        $assigneeIds = array_values(
            array_unique(
                array_map('intval', $assigneeIds)
            )
        );

        if ($assigneeIds === []) {
            return [];
        }

        $validIds = Employee::query()
            ->whereIn('employees.id', $assigneeIds)
            ->where('employees.status', 'active')
            ->whereExists(function ($query) use ($project): void {
                $query->selectRaw('1')
                    ->from('project_members')
                    ->whereColumn(
                        'project_members.employee_id',
                        'employees.id'
                    )
                    ->where('project_members.project_id', $project->id)
                    ->whereNull('project_members.removed_at');
            })
            ->pluck('employees.id')
            ->map(fn($id): int => (int) $id)
            ->all();

        $invalidIds = array_diff($assigneeIds, $validIds);

        if ($invalidIds !== []) {
            throw ValidationException::withMessages([
                'assignees' => 'Every assignee must be an active member of the selected project.',
            ]);
        }

        return $assigneeIds;
    }

    /**
     * Preserve task assignment history using removed_at.
     *
     * @param array<int> $employeeIds
     */
    private function syncAssignees(Task $task, array $employeeIds): void
    {
        $now = now();

        $existing = DB::table('task_assignees')
            ->where('task_id', $task->id)
            ->lockForUpdate()
            ->get();

        $currentActiveIds = $existing
            ->whereNull('removed_at')
            ->pluck('employee_id')
            ->map(fn($id): int => (int) $id)
            ->all();

        $employeeIds = array_values(array_unique($employeeIds));

        $toRemove = array_diff($currentActiveIds, $employeeIds);
        $toAdd = array_diff($employeeIds, $currentActiveIds);

        if ($toRemove !== []) {
            DB::table('task_assignees')
                ->where('task_id', $task->id)
                ->whereIn('employee_id', $toRemove)
                ->whereNull('removed_at')
                ->update([
                    'removed_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        foreach ($toAdd as $employeeId) {
            $existingAssignment = $existing
                ->firstWhere('employee_id', $employeeId);

            if ($existingAssignment) {
                DB::table('task_assignees')
                    ->where('id', $existingAssignment->id)
                    ->update([
                        'assigned_at' => $now,
                        'removed_at' => null,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('task_assignees')->insert([
                'task_id' => $task->id,
                'employee_id' => $employeeId,
                'assigned_at' => $now,
                'removed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
