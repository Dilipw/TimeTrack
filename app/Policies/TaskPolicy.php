<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user)
            || $user->hasAnyRole(['project_manager', 'employee']);
    }

    public function view(User $user, Task $task): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        if (! $employee) {
            return false;
        }

        if ($user->hasRole('project_manager')) {
            return $task->project->project_manager_id === $employee->id;
        }

        if ($user->hasRole('employee')) {
            return $task->activeAssignees()
                ->whereKey($employee->id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user)
            || $user->hasRole('project_manager');
    }

    public function update(User $user, Task $task): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        return $user->hasRole('project_manager')
            && $employee !== null
            && $task->project->project_manager_id === $employee->id;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        return $user->hasRole('project_manager')
            && $employee !== null
            && $task->project->project_manager_id === $employee->id;
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Task $task): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function replicate(User $user, Task $task): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        return $user->hasRole('project_manager')
            && $employee !== null
            && $task->project->project_manager_id === $employee->id;
    }

    public function reorder(User $user): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}