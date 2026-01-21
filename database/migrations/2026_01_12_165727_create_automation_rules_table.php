<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_account_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('trigger_type'); // keyword_match, time_based, engagement_threshold, etc.
            $table->json('trigger_conditions'); // Conditions that trigger the rule
            $table->string('action_type'); // auto_reply, auto_like, auto_dm, post_content, etc.
            $table->json('action_parameters'); // Parameters for the action
            $table->boolean('is_active')->default(true);
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};