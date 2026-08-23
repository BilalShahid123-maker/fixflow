<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_verified')->default(false)->index();
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('contractor_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->string('trade');
            $table->unsignedInteger('base_cost_cents')->nullable();
            $table->unsignedInteger('hourly_rate_cents')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['contractor_id', 'trade']);
        });

        Schema::create('contractor_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_booked')->default(false);
            $table->timestamps();

            $table->index(['contractor_id', 'starts_at', 'is_booked']);
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contractor_id')->constrained()->restrictOnDelete();
            $table->string('status')->default(\App\Enums\WorkOrderStatus::Draft->value)->index();
            $table->unsignedInteger('estimated_cost_cents')->nullable();
            $table->unsignedInteger('final_cost_cents')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('scheduled_until')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('contractor_availability');
        Schema::dropIfExists('contractor_services');
        Schema::dropIfExists('contractors');
    }
};
