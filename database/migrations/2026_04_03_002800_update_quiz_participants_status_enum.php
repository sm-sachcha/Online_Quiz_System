<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE quiz_participants MODIFY COLUMN status ENUM('registered', 'joined', 'taking_quiz', 'completed', 'disqualified', 'left') DEFAULT 'registered'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE quiz_participants MODIFY COLUMN status ENUM('registered', 'joined', 'taking_quiz', 'disqualified', 'left') DEFAULT 'registered'");
    }
};