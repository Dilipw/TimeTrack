<?php

namespace App\Policies;

use App\Enums\EmployeeStatus;
use App\Enums\ProjectStatus;
use App\Enums\TimeEntryStatus;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryPolicy
{
    public function view(User $user, TimeEntry $timeEntry): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $employee = $user->employee;

        if (! $employee) {
            return false;
        }

        return $timeEntry->employee_id === $employee->id
            || $this->managesProject($employee, $timeEntry);
    }

    public function create(User $user): bool
    {
        $employee = $user->employee;

        return $employee?->status === EmployeeStatus::ACTIVE;
    }

    public function update(User $user, TimeEntry $timeEntry): bool
    {
        if ($this->isAdmin($user)) {
            return in_array(
                $timeEntry->status,
                [
                    TimeEntryStatus::DRAFT,
                    TimeEntryStatus::REJECTED,
                ],
                true
            );
        }

        $employee = $user->employee;

        if (! $employee || $employee->status !== EmployeeStatus::ACTIVE) {
            return false;
        }

        return $timeEntry->employee_id === $employee->id
            && in_array(
                $timeEntry->status,
                [
                    TimeEntryStatus::DRAFT,
                    TimeEntryStatus::REJECTED,
                ],
                true
            );
    }

    public function delete(User $user, TimeEntry $timeEntry): bool
    {
        if ($this->isAdmin($user)) {
            return $timeEntry->status === TimeEntryStatus::DRAFT;
        }

        $employee = $user->employee;

        return $employee
            && $employee->status === EmployeeStatus::ACTIVE
            && $timeEntry->employee_id === $employee->id
            && $timeEntry->status === TimeEntryStatus::DRAFT;
    }

    public function submit(User $user, TimeEntry $timeEntry): bool
    {
        $employee = $user->employee;

        if (! $employee || $employee->status !== EmployeeStatus::ACTIVE) {
            return false;
        }

        return $timeEntry->employee_id === $employee->id
            && in_array(
                $timeEntry->status,
                [
                    TimeEntryStatus::DRAFT,
                    TimeEntryStatus::REJECTED,
                ],
                true
            );
    }

    public function approve(User $user, TimeEntry $timeEntry): bool
    {
        if ($this->isAdmin($user)) {
            return $timeEntry->status === TimeEntryStatus::SUBMITTED;
        }

        $employee = $user->employee;

        if (! $employee || $employee->status !== EmployeeStatus::ACTIVE) {
            return false;
        }

        return $timeEntry->status === TimeEntryStatus::SUBMITTED
            && $timeEntry->employee_id !== $employee->id
            && $this->managesProject($employee, $timeEntry);
    }

    public function reject(User $user, TimeEntry $timeEntry): bool
    {
        if ($this->isAdmin($user)) {
            return $timeEntry->status === TimeEntryStatus::SUBMITTED;
        }

        $employee = $user->employee;

        if (! $employee || $employee->status !== EmployeeStatus::ACTIVE) {
            return false;
        }

        return $timeEntry->status === TimeEntryStatus::SUBMITTED
            && $timeEntry->employee_id !== $employee->id
            && $this->managesProject($employee, $timeEntry);
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('super_admin');
    }

    private function managesProject(
        Employee $employee,
        TimeEntry $timeEntry
    ): bool {
        return $timeEntry->project?->project_manager_id === $employee->id;
    }
}