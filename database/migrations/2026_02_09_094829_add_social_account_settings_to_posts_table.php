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
        Schema::table('posts', function (Blueprint $table) {
            // Add platform-specific settings fields
            $table->json('facebook_settings')->nullable();
            $table->json('instagram_settings')->nullable();
            $table->json('x_settings')->nullable(); // X/Twitter settings
            $table->json('linkedin_settings')->nullable();
            $table->json('youtube_settings')->nullable();
            $table->json('tiktok_settings')->nullable();
            $table->json('other_platform_settings')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_settings',
                'instagram_settings',
                'x_settings',
                'linkedin_settings',
                'youtube_settings',
                'tiktok_settings',
                'other_platform_settings'
            ]);
        });
    }
};
