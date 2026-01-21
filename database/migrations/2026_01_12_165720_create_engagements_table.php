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
        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('engagement_type'); // like, comment, share, dm, follow, unfollow
            $table->string('platform');
            $table->string('platform_engagement_id');
            $table->string('actor_username'); // Who engaged
            $table->string('actor_full_name')->nullable();
            $table->string('actor_platform_id');
            $table->text('content')->nullable(); // Comment content, DM content
            $table->json('platform_data')->nullable();
            $table->boolean('is_automated_response')->default(false);
            $table->timestamp('engaged_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('engagements');
    }
};