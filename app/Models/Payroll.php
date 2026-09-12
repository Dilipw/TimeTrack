<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'employee_id',
    'period_start',
    'period_end',
    'hourly_rate',
    'regular_minutes',
    'overtime_minutes',
    'overtime_multiplier',
    'regular_amount',
    'overtime_amount',
    'adjustment_amount',
    'deduction_amount',
    'gross_amount',
    'net_amount',
    'status',
    'finalized_at',
    'finalized_by',
])]
class Payroll extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(PayrollTimeEntry::class);
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',

            'hourly_rate' => 'decimal:2',

            'regular_minutes' => 'integer',
            'overtime_minutes' => 'integer',

            'overtime_multiplier' => 'decimal:2',

            'regular_amount' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'adjustment_amount' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',

            'status' => PayrollStatus::class,

            'finalized_at' => 'datetime',
        ];
    }
}