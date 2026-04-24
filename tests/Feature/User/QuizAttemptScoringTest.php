<?php

namespace Tests\Feature\User;

use App\Models\Category;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_choice_submission_returns_updated_score_and_counts_for_a_correct_answer(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        $quiz = $this->createQuizFor($user);
        $question = $this->createQuestion($quiz, [
            'question_text' => 'Capital of Bangladesh?',
            'points' => 10,
            'order' => 1,
        ]);
        $this->createQuestion($quiz, [
            'question_text' => 'Second question placeholder',
            'points' => 10,
            'order' => 2,
        ]);

        $correctOption = Option::create([
            'question_id' => $question->id,
            'option_text' => 'Dhaka',
            'is_correct' => true,
            'order' => 1,
        ]);

        Option::create([
            'question_id' => $question->id,
            'option_text' => 'Chattogram',
            'is_correct' => false,
            'order' => 2,
        ]);

        $attempt = $this->createAttempt($quiz, $user, [
            'total_questions' => 2,
            'total_points' => 20,
        ]);

        $response = $this->actingAs($user)->postJson(route('user.quiz.submit', [$quiz, $attempt]), [
            'question_id' => $question->id,
            'option_id' => $correctOption->id,
            'time_taken' => 8,
            'question_type' => 'single_choice',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_correct' => true,
                'points_earned' => 10,
                'current_score' => 10,
                'correct_answers' => 1,
                'incorrect_answers' => 0,
                'answered_count' => 1,
                'total_questions' => 2,
            ]);

        $attempt->refresh();

        $this->assertSame(10, $attempt->score);
        $this->assertSame(1, $attempt->correct_answers);
        $this->assertSame(0, $attempt->incorrect_answers);
    }

    public function test_single_choice_submission_returns_updated_score_and_counts_for_an_incorrect_answer(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        $quiz = $this->createQuizFor($user);
        $question = $this->createQuestion($quiz, [
            'question_text' => '2 + 2 = ?',
            'points' => 5,
            'order' => 1,
        ]);
        $this->createQuestion($quiz, [
            'question_text' => 'Second question placeholder',
            'points' => 5,
            'order' => 2,
        ]);

        Option::create([
            'question_id' => $question->id,
            'option_text' => '4',
            'is_correct' => true,
            'order' => 1,
        ]);

        $wrongOption = Option::create([
            'question_id' => $question->id,
            'option_text' => '5',
            'is_correct' => false,
            'order' => 2,
        ]);

        $attempt = $this->createAttempt($quiz, $user, [
            'total_questions' => 2,
            'total_points' => 10,
        ]);

        $response = $this->actingAs($user)->postJson(route('user.quiz.submit', [$quiz, $attempt]), [
            'question_id' => $question->id,
            'option_id' => $wrongOption->id,
            'time_taken' => 6,
            'question_type' => 'single_choice',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_correct' => false,
                'points_earned' => 0,
                'current_score' => 0,
                'correct_answers' => 0,
                'incorrect_answers' => 1,
                'answered_count' => 1,
                'total_questions' => 2,
            ]);

        $attempt->refresh();

        $this->assertSame(0, $attempt->score);
        $this->assertSame(0, $attempt->correct_answers);
        $this->assertSame(1, $attempt->incorrect_answers);
    }

    private function createQuizFor(User $owner): Quiz
    {
        $category = Category::create([
            'name' => 'Scoring Category',
            'description' => 'Quiz scoring tests',
            'icon' => 'fas fa-star',
            'color' => '#0d6efd',
            'is_active' => true,
            'created_by' => $owner->id,
        ]);

        return Quiz::create([
            'title' => 'Scoring Quiz ' . uniqid(),
            'description' => 'Scoring behavior test quiz',
            'category_id' => $category->id,
            'duration_minutes' => 30,
            'total_questions' => 2,
            'passing_score' => 50,
            'is_random_questions' => false,
            'is_random_options' => false,
            'is_published' => true,
            'scheduled_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'max_attempts' => 1,
            'total_points' => 20,
            'settings' => null,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }

    private function createQuestion(Quiz $quiz, array $attributes = []): Question
    {
        return Question::create(array_merge([
            'quiz_id' => $quiz->id,
            'question_text' => 'Test question',
            'question_type' => 'single_choice',
            'points' => 10,
            'time_seconds' => 30,
            'order' => 1,
            'explanation' => null,
            'show_answer' => true,
            'metadata' => null,
            'is_active' => true,
            'created_by' => $quiz->created_by,
        ], $attributes));
    }

    private function createAttempt(Quiz $quiz, User $user, array $attributes = []): QuizAttempt
    {
        return QuizAttempt::create(array_merge([
            'user_id' => $user->id,
            'participant_id' => null,
            'quiz_id' => $quiz->id,
            'score' => 0,
            'total_points' => $quiz->total_points,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'total_questions' => $quiz->total_questions,
            'question_sequence' => [],
            'option_sequences' => [],
            'started_at' => now()->subMinute(),
            'ended_at' => null,
            'status' => 'in_progress',
            'cheating_logs' => null,
            'ip_address' => '127.0.0.1',
        ], $attributes));
    }
}
