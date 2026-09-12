<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimeEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:TimeEntry');
    }

    public function view(User $user, TimeEntry $timeEntry): bool
    {
        return $user->can('View:TimeEntry');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:TimeEntry');
    }

    public function update(User $user, TimeEntry $timeEntry): bool
    {
        return $user->can('Update:TimeEntry')
            && in_array(
                $timeEntry->status->value,
                ['draft', 'rejected'],
                true
            );
    }

    public function delete(User $user, TimeEntry $timeEntry): bool
    {
        return $user->can('Delete:TimeEntry');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:TimeEntry');
    }

    public function restore(User $user, TimeEntry $timeEntry): bool
    {
        return $user->can('Restore:TimeEntry');
    }

    public function forceDelete(User $user, TimeEntry $timeEntry): bool
    {
        return $user->can('ForceDelete:TimeEntry');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:TimeEntry');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:TimeEntry');
    }

    public function replicate(User $user, TimeEntry $timeEntry): bool
    {
        return $user->can('Replicate:TimeEntry');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:TimeEntry');
    }

    /**
     * Determine whether the user can approve a submitted time entry.
     */
    public function approve(User $user, TimeEntry $timeEntry): bool
    {
        if ($timeEntry->status->value !== 'submitted') {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $user->hasRole('project_manager')) {
            return false;
        }

        $employee = $user->employee;

        if (! $employee) {
            return false;
        }

        if ($timeEntry->employee_id === $employee->id) {
            return false;
        }

        return $timeEntry->project->project_manager_id === $employee->id;
    }

    /**
     * Determine whether the user can reject a submitted time entry.
     */
    public function reject(User $user, TimeEntry $timeEntry): bool
    {
        if ($timeEntry->status->value !== 'submitted') {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $user->hasRole('project_manager')) {
            return false;
        }

        $employee = $user->employee;

        if (! $employee) {
            return false;
        }

        if ($timeEntry->employee_id === $employee->id) {
            return false;
        }

        return $timeEntry->project->project_manager_id === $employee->id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'admin',
        ]);
    }
}
