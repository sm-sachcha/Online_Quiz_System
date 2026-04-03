<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            DB::statement("UPDATE quiz_participants SET status = 'left' WHERE status = 'abandoned'");
            DB::statement("UPDATE quiz_participants SET status = 'joined' WHERE status = 'registered'");
            DB::statement("ALTER TABLE quiz_participants MODIFY COLUMN status ENUM('joined', 'taking_quiz', 'completed', 'left') DEFAULT 'joined'");
        });
    }

    public function down(): void
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            DB::statement("ALTER TABLE quiz_participants MODIFY COLUMN status ENUM('joined', 'left', 'registered') DEFAULT 'joined'");
        });
    }
};