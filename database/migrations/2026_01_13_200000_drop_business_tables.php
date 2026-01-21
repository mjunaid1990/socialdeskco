<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop business_user pivot table first (due to foreign key constraints)
        Schema::dropIfExists('business_user');
        
        // Drop business_settings table
        Schema::dropIfExists('business_settings');
        
        // Finally drop the businesses table
        Schema::dropIfExists('businesses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate businesses table
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('status')->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        
        // Recreate business_settings table
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('setting_key');
            $table->json('setting_value')->nullable();
            $table->string('setting_type')->default('general');
            $table->timestamps();
            
            $table->unique(['business_id', 'setting_key']);
        });
        
        // Recreate business_user table
        Schema::create('business_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('member'); // admin, manager, member
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->unique(['business_id', 'user_id']);
        });
    }
};