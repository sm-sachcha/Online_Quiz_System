<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            // Change status from ENUM to VARCHAR
            $table->string('status', 20)->default('registered')->change();
        });
    }

    public function down()
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            // Revert back to ENUM if needed
            $table->enum('status', ['registered', 'joined', 'left', 'completed'])->default('registered')->change();
        });
    }
};