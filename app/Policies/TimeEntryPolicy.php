<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EmployeeStatus;
use App\Enums\TimeEntryStatus;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimeEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user)
            || $user->hasAnyRole([
                'project_manager',
                'employee',
            ]);
    }

    public function view(User $user, TimeEntry $timeEntry): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        if (!$employee) {
            return false;
        }

        // Employee can view their own time entries.
        if (
            $user->hasRole('employee')
            && $timeEntry->employee_id === $employee->id
        ) {
            return true;
        }

        // Project manager can view entries from projects
        // they manage.
        if ($user->hasRole('project_manager')) {
            return $timeEntry->project->project_manager_id === $employee->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (!$user->hasRole('employee')) {
            return false;
        }

        $employee = $user->employee;

        return $employee !== null
            && $employee->status === EmployeeStatus::ACTIVE;
    }

    public function update(User $user, TimeEntry $timeEntry): bool
    {
        if (!in_array(
            $timeEntry->status,
            [
                TimeEntryStatus::DRAFT,
                TimeEntryStatus::REJECTED,
            ],
            true
        )) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        if (!$employee) {
            return false;
        }

        return $user->hasRole('employee')
            && $timeEntry->employee_id === $employee->id
            && $employee->status === EmployeeStatus::ACTIVE;
    }

    public function delete(
        User $user,
        TimeEntry $timeEntry
    ): bool {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(
        User $user,
        TimeEntry $timeEntry
    ): bool {
        return $this->isAdmin($user);
    }

    public function forceDelete(
        User $user,
        TimeEntry $timeEntry
    ): bool {
        return $this->isAdmin($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function replicate(
        User $user,
        TimeEntry $timeEntry
    ): bool {
        return $this->isAdmin($user);
    }

    public function reorder(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function approve(
        User $user,
        TimeEntry $timeEntry
    ): bool {
        if ($timeEntry->status !== TimeEntryStatus::SUBMITTED) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if (!$user->hasRole('project_manager')) {
            return false;
        }

        $employee = $user->employee;

        if (!$employee) {
            return false;
        }

        // A user cannot approve their own time entry.
        if ($timeEntry->employee_id === $employee->id) {
            return false;
        }

        // PM can approve only entries from their own project.
        return $timeEntry->project->project_manager_id === $employee->id;
    }

    public function reject(
        User $user,
        TimeEntry $timeEntry
    ): bool {
        if ($timeEntry->status !== TimeEntryStatus::SUBMITTED) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if (!$user->hasRole('project_manager')) {
            return false;
        }

        $employee = $user->employee;

        if (!$employee) {
            return false;
        }

        // A user cannot reject their own time entry.
        if ($timeEntry->employee_id === $employee->id) {
            return false;
        }

        return $timeEntry->project->project_manager_id === $employee->id;
    }

    public function submit(
        User $user,
        TimeEntry $timeEntry
    ): bool {
        if (!in_array(
            $timeEntry->status,
            [
                TimeEntryStatus::DRAFT,
                TimeEntryStatus::REJECTED,
            ],
            true
        )) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        if (!$employee) {
            return false;
        }

        return $user->hasRole('employee')
            && $timeEntry->employee_id === $employee->id
            && $employee->status === EmployeeStatus::ACTIVE;
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'admin',
        ]);
    }
}