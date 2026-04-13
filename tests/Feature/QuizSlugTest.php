<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_slug_is_made_unique_when_titles_match(): void
    {
        $firstAdmin = User::factory()->create(['role' => 'admin']);
        $secondAdmin = User::factory()->create(['role' => 'admin']);

        $firstQuiz = Quiz::create([
            'title' => 'Quiz 1',
            'description' => null,
            'category_id' => null,
            'duration_minutes' => 30,
            'passing_score' => 50,
            'is_random_questions' => false,
            'max_attempts' => 1,
            'scheduled_at' => null,
            'ends_at' => null,
            'is_published' => false,
            'created_by' => $firstAdmin->id,
        ]);

        $secondQuiz = Quiz::create([
            'title' => 'Quiz 1',
            'description' => null,
            'category_id' => null,
            'duration_minutes' => 30,
            'passing_score' => 50,
            'is_random_questions' => false,
            'max_attempts' => 1,
            'scheduled_at' => null,
            'ends_at' => null,
            'is_published' => false,
            'created_by' => $secondAdmin->id,
        ]);

        $this->assertSame('quiz-1', $firstQuiz->slug);
        $this->assertSame('quiz-1-1', $secondQuiz->slug);
    }
}
