<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('project_code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->foreignId('project_manager_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->enum('status', [
                'planning',
                'active',
                'completed',
                'cancelled',
            ])->default('planning');

            $table->decimal('budget', 12, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('project_manager_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};