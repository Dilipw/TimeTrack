<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\ProjectStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectMemberService
{
    public function add(
        User $actor,
        Project $project,
        int $employeeId,
    ): ProjectMember {
        return DB::transaction(function () use ($actor, $project, $employeeId): ProjectMember {
            $project = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->id);

            $this->authorize($actor, $project);
            $this->validateProject($project);

            $employee = Employee::query()
                ->whereKey($employeeId)
                ->where('status', EmployeeStatus::ACTIVE)
                ->first();

            if (! $employee) {
                throw ValidationException::withMessages([
                    'employee_id' => 'The selected employee must be active.',
                ]);
            }

            $membership = ProjectMember::query()
                ->where('project_id', $project->id)
                ->where('employee_id', $employee->id)
                ->lockForUpdate()
                ->first();

            if ($membership) {
                if ($membership->removed_at === null) {
                    throw ValidationException::withMessages([
                        'employee_id' => 'This employee is already an active member of the project.',
                    ]);
                }

                $membership->update([
                    'assigned_at' => now(),
                    'removed_at' => null,
                ]);

                return $membership->fresh(['project', 'employee']);
            }

            $membership = ProjectMember::query()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'assigned_at' => now(),
                'removed_at' => null,
            ]);

            return $membership->fresh(['project', 'employee']);
        });
    }

    public function remove(
        User $actor,
        Project $project,
        int $employeeId,
    ): ProjectMember {
        return DB::transaction(function () use ($actor, $project, $employeeId): ProjectMember {
            $project = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->id);

            $this->authorize($actor, $project);
            $this->validateProject($project);

            $membership = ProjectMember::query()
                ->where('project_id', $project->id)
                ->where('employee_id', $employeeId)
                ->lockForUpdate()
                ->first();

            if (! $membership || $membership->removed_at !== null) {
                throw ValidationException::withMessages([
                    'employee_id' => 'The employee is not an active member of this project.',
                ]);
            }

            $membership->update([
                'removed_at' => now(),
            ]);

            return $membership->fresh(['project', 'employee']);
        });
    }

    private function authorize(User $actor, Project $project): void
    {
        if ($actor->hasAnyRole(['super_admin', 'admin'])) {
            return;
        }

        if (! $actor->hasRole('project_manager')) {
            throw ValidationException::withMessages([
                'project' => 'You are not authorized to manage project members.',
            ]);
        }

        $employee = $actor->employee;

        if (! $employee || $project->project_manager_id !== $employee->id) {
            throw ValidationException::withMessages([
                'project' => 'You can only manage members of projects assigned to you.',
            ]);
        }
    }

    private function validateProject(Project $project): void
    {
        if (
            $project->status === ProjectStatus::COMPLETED
            || $project->status === ProjectStatus::CANCELLED
        ) {
            throw ValidationException::withMessages([
                'project' => 'Project members cannot be changed on a completed or cancelled project.',
            ]);
        }
    }
}