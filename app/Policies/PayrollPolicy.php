<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Payroll $payroll): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $this->isAdmin($user)
            && $payroll->status->value === 'draft';
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        return $this->isAdmin($user)
            && $payroll->status->value === 'draft';
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Payroll $payroll): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Payroll $payroll): bool
    {
        return $this->isAdmin($user)
            && $payroll->status->value === 'draft';
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'admin',
        ]);
    }
}
