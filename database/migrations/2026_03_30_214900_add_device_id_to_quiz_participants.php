<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_participants', 'device_id')) {
                $table->string('device_id')->nullable()->after('guest_name');
                $table->index('device_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_participants', 'device_id')) {
                $table->dropColumn('device_id');
            }
        });
    }
};