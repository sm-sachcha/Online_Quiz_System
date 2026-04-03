<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaderboards', function (Blueprint $table) {
            // Drop foreign key first
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            // Make user_id nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Add participant_id column for guests
            if (!Schema::hasColumn('leaderboards', 'participant_id')) {
                $table->unsignedBigInteger('participant_id')->nullable()->after('user_id');
                $table->foreign('participant_id')->references('id')->on('quiz_participants')->onDelete('cascade');
            }
            
            // Re-add foreign key for user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['participant_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->dropColumn('participant_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};