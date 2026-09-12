<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_time_entries', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('payroll_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('time_entry_id')
                ->constrained()
                ->restrictOnDelete();

            // Snapshot of approved working time at payroll finalization.
            $table->unsignedInteger('working_minutes');

            // regular / overtime
            $table->string('entry_type', 20);

            // Snapshot of rate used for this particular entry.
            $table->decimal('hourly_rate', 12, 2);

            // Final amount calculated for this entry.
            $table->decimal('amount', 12, 2);

            $table->timestamps();

            $table->unique([
                'payroll_id',
                'time_entry_id',
            ]);

            $table->index('time_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_time_entries');
    }
};