<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->restrictOnDelete();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->restrictOnDelete();

            $table->date('work_date');

            $table->time('start_time');
            $table->time('end_time');

            $table->unsignedInteger('break_minutes')
                ->default(0);

            $table->unsignedInteger('working_minutes');

            $table->enum('entry_type', [
                'regular',
                'overtime',
            ])->default('regular');

            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'rejected',
                'cancelled',
            ])->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'employee_id',
                'work_date',
            ]);

            $table->index([
                'project_id',
                'work_date',
            ]);

            $table->index('task_id');
            $table->index('status');
            $table->index('entry_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};