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
            // Make social_account_id nullable since we're using many-to-many relationship
            $table->dropForeign(['social_account_id']);
            $table->foreignId('social_account_id')->nullable()->change();
            $table->foreign('social_account_id')->references('id')->on('social_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['social_account_id']);
            $table->foreignId('social_account_id')->nullable(false)->change();
            $table->foreign('social_account_id')->references('id')->on('social_accounts')->onDelete('cascade');
        });
    }
};
