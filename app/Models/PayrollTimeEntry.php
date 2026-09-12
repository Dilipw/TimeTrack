<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TimeEntryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payroll_id',
    'time_entry_id',
    'working_minutes',
    'entry_type',
    'hourly_rate',
    'amount',
])]
class PayrollTimeEntry extends Model
{
    use HasFactory;

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

    protected function casts(): array
    {
        return [
            'working_minutes' => 'integer',
            'entry_type' => TimeEntryType::class,
            'hourly_rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }
}