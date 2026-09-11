<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->restrictOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('removed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'task_id',
                'employee_id',
            ]);

            $table->index('employee_id');
            $table->index('removed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
    }
};