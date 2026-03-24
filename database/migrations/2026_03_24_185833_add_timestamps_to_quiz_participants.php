<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            // Only add if they don't exist
            if (!Schema::hasColumn('quiz_participants', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down()
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};