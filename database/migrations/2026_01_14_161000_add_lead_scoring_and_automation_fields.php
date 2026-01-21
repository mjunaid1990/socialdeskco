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
        Schema::table('leads', function (Blueprint $table) {
            // Add columns only if they don't exist
            if (!Schema::hasColumn('leads', 'score')) {
                $table->integer('score')->default(0)->after('status');
            }
            if (!Schema::hasColumn('leads', 'qualification')) {
                $table->string('qualification')->default('cold')->after('score'); // hot, warm, cold
            }
            if (!Schema::hasColumn('leads', 'last_followup_at')) {
                $table->timestamp('last_followup_at')->nullable()->after('contacted_at');
            }
            if (!Schema::hasColumn('leads', 'followup_count')) {
                $table->integer('followup_count')->default(0)->after('last_followup_at');
            }
            if (!Schema::hasColumn('leads', 'automation_history')) {
                $table->json('automation_history')->nullable()->after('platform_data');
            }
        });

        Schema::table('automation_rules', function (Blueprint $table) {
            $table->string('trigger_type', 50)->change();
            
            // Add new trigger types for leads (only if they don't exist)
            if (!Schema::hasColumn('automation_rules', 'platform_filter')) {
                $table->after('trigger_type', function (Blueprint $table) {
                    $table->string('platform_filter')->nullable();
                    $table->json('trigger_conditions')->nullable();
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'score',
                'qualification',
                'last_followup_at',
                'followup_count',
                'automation_history'
            ]);
        });

        Schema::table('automation_rules', function (Blueprint $table) {
            $table->string('trigger_type')->change();
            $table->dropColumn([
                'platform_filter',
                'trigger_conditions'
            ]);
        });
    }
};
