<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            // Add synchronized mode fields
            $table->boolean('is_synchronized')->default(false)->after('is_published');
            $table->unsignedBigInteger('current_question_id')->nullable()->after('is_synchronized');
            $table->integer('current_question_number')->default(0)->after('current_question_id');
            $table->timestamp('current_question_started_at')->nullable()->after('current_question_number');
            
            // Add foreign key for current_question_id
            $table->foreign('current_question_id')
                ->references('id')
                ->on('questions')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['current_question_id']);
            $table->dropColumn(['is_synchronized', 'current_question_id', 'current_question_number', 'current_question_started_at']);
        });
    }
};
