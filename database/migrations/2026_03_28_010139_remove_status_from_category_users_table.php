<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_users', function (Blueprint $table) {
            if (Schema::hasColumn('category_users', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('category_users', function (Blueprint $table) {
            $table->string('status')->nullable()->after('user_id');
        });
    }
};