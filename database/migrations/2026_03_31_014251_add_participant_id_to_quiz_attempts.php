<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'participant_id')) {
                $table->unsignedBigInteger('participant_id')->nullable()->after('user_id');
                $table->foreign('participant_id')->references('id')->on('quiz_participants')->onDelete('set null');
                $table->index('participant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_attempts', 'participant_id')) {
                $table->dropForeign(['participant_id']);
                $table->dropColumn('participant_id');
            }
        });
    }
};