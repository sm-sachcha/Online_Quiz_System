<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if leaderboards table exists
        if (Schema::hasTable('leaderboards')) {
            // Drop existing foreign keys first
            Schema::table('leaderboards', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {}
                try {
                    $table->dropForeign(['participant_id']);
                } catch (\Exception $e) {}
            });
            
            // Make user_id nullable
            Schema::table('leaderboards', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
            
            // Add participant_id column if it doesn't exist
            if (!Schema::hasColumn('leaderboards', 'participant_id')) {
                Schema::table('leaderboards', function (Blueprint $table) {
                    $table->unsignedBigInteger('participant_id')->nullable()->after('user_id');
                });
            }
            
            // Re-add foreign keys
            Schema::table('leaderboards', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
                
                $table->foreign('participant_id')
                      ->references('id')
                      ->on('quiz_participants')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['participant_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->dropColumn('participant_id');
        });
    }
};