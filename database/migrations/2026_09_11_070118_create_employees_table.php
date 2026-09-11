<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->string('employee_code', 50)->unique();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('first_name', 100);
            $table->string('last_name', 100);

            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete();

            $table->foreignId('designation_id')
                ->constrained('designations')
                ->restrictOnDelete();

            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->date('joining_date');

            $table->decimal('hourly_rate', 12, 2);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('department_id');
            $table->index('designation_id');
            $table->index('manager_id');
            $table->index('status');
            $table->index('joining_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};