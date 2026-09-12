<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user)
            || $user->hasAnyRole(['project_manager', 'employee']);
    }

    public function view(User $user, Project $project): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        if (! $employee) {
            return false;
        }

        if ($user->hasRole('project_manager')) {
            return $project->project_manager_id === $employee->id;
        }

        if ($user->hasRole('employee')) {
            return $project->activeMembers()
                ->whereKey($employee->id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Project $project): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        return $user->hasRole('project_manager')
            && $employee !== null
            && $project->project_manager_id === $employee->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function replicate(User $user, Project $project): bool
    {
        return $this->isAdmin($user);
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