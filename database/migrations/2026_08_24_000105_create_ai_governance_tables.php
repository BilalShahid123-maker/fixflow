<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('agent');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->string('status')->default('running');
            $table->text('error')->nullable();
            $table->json('input_payload')->nullable();
            $table->json('output')->nullable();
            $table->timestamps();

            $table->index(['agent', 'status']);
            $table->index('created_at');
        });

        Schema::create('ai_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type');
            $table->unsignedTinyInteger('authority_level')->default(1);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default(\App\Enums\ActionStatus::Proposed->value)->index();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['action_type', 'status']);
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_action_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default(\App\Enums\ApprovalStatus::Pending->value);
            $table->text('note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('ai_actions');
        Schema::dropIfExists('ai_runs');
    }
};
