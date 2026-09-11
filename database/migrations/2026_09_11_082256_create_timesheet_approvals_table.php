<?php

use App\Enums\ApprovalAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheet_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('time_entry_id')
                ->constrained('time_entries')
                ->restrictOnDelete();

            $table->foreignId('approver_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('action', array_column(
                ApprovalAction::cases(),
                'value'
            ));

            $table->text('rejection_reason')->nullable();

            $table->timestamp('acted_at');

            $table->timestamps();

            $table->index(['time_entry_id', 'acted_at']);
            $table->index(['approver_user_id', 'acted_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_approvals');
    }
};