<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('user_id');
            $table->boolean('is_guest')->default(false)->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'is_guest']);
        });
    }
};