<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->string('severity');
            $table->boolean('emergency')->default(false);
            $table->string('split')->default('eval'); // train | eval | holdout
            $table->string('source_note')->nullable();
            $table->timestamps();

            $table->index(['split']);
        });

        Schema::create('eval_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eval_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_run_id')->nullable()->constrained('ai_runs')->nullOnDelete();
            $table->string('category_expected');
            $table->string('category_predicted')->nullable();
            $table->string('severity_expected');
            $table->string('severity_predicted')->nullable();
            $table->boolean('category_correct')->default(false);
            $table->boolean('severity_correct')->default(false);
            $table->boolean('critical_expected')->default(false);
            $table->boolean('critical_correct')->default(false);
            $table->float('confidence')->nullable();
            $table->timestamps();

            $table->index(['eval_case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_runs');
        Schema::dropIfExists('eval_cases');
    }
};
