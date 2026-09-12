<?php

use App\Enums\PayrollStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained()
                ->restrictOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            // Historical snapshot of employee's hourly rate.
            $table->decimal('hourly_rate', 12, 2);

            $table->unsignedInteger('regular_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);

            // Historical snapshot of the overtime multiplier.
            $table->decimal('overtime_multiplier', 5, 2)->default(1.50);

            $table->decimal('regular_amount', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);

            // Positive = additional earning.
            // Negative = adjustment deduction.
            $table->decimal('adjustment_amount', 12, 2)->default(0);

            $table->decimal('deduction_amount', 12, 2)->default(0);

            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);

            $table->enum(
                'status',
                array_column(PayrollStatus::cases(), 'value')
            )->default(PayrollStatus::DRAFT->value);

            $table->timestamp('finalized_at')->nullable();

            $table->foreignId('finalized_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->softDeletes();

            // An employee can have only one payroll for an exact period.
            $table->unique([
                'employee_id',
                'period_start',
                'period_end',
            ]);

            $table->index([
                'employee_id',
                'period_start',
                'period_end',
            ]);

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};