<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, update existing records to use allowed values
        DB::statement("UPDATE quiz_participants SET status = 'joined' WHERE status = 'joined'");
        DB::statement("UPDATE quiz_participants SET status = 'left' WHERE status = 'left'");
        DB::statement("UPDATE quiz_participants SET status = 'registered' WHERE status = 'registered'");
        
        // Then modify the enum to include all needed values
        DB::statement("ALTER TABLE quiz_participants MODIFY status ENUM('joined', 'taking_quiz', 'left', 'registered') DEFAULT 'registered'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE quiz_participants MODIFY status ENUM('joined', 'left', 'registered') DEFAULT 'registered'");
    }
};