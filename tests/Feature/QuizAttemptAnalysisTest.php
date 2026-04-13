<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\QuizParticipantsPayloadService;
use App\Services\ResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_answer_submission_returns_fresh_correct_and_incorrect_counts(): void
    {
        $this->withoutExceptionHandling();

        $this->mock(QuizParticipantsPayloadService::class, function ($mock) {
            $mock->shouldReceive('build')->andReturn([]);
        });

        $user = User::factory()->create(['role' => 'user']);

        $quiz = Quiz::create([
            'title' => 'Science Quiz',
            'description' => null,
            'category_id' => null,
            'duration_minutes' => 30,
            'passing_score' => 50,
            'is_random_questions' => false,
            'is_published' => true,
            'scheduled_at' => now()->subMinute(),
            'ends_at' => now()->addMinutes(30),
            'max_attempts' => 1,
            'created_by' => $user->id,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is H2O?',
            'question_type' => 'single_choice',
            'points' => 5,
            'time_seconds' => 30,
            'order' => 1,
            'created_by' => $user->id,
        ]);

        $correctOption = Option::create([
            'question_id' => $question->id,
            'option_text' => 'Water',
            'is_correct' => true,
            'order' => 1,
        ]);

        Option::create([
            'question_id' => $question->id,
            'option_text' => 'Oxygen',
            'is_correct' => false,
            'order' => 2,
        ]);

        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'participant_id' => null,
            'quiz_id' => $quiz->id,
            'score' => 0,
            'total_points' => 5,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'total_questions' => 1,
            'started_at' => now()->subSeconds(10),
            'status' => 'in_progress',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('user.quiz.submit', ['quiz' => $quiz->id, 'attempt' => $attempt->id]), [
                'question_id' => $question->id,
                'option_id' => $correctOption->id,
                'time_taken' => 7,
                'question_type' => 'single_choice',
            ]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $response
            ->assertJsonPath('success', true)
            ->assertJsonPath('current_score', 5)
            ->assertJsonPath('correct_answers', 1)
            ->assertJsonPath('incorrect_answers', 0)
            ->assertJsonPath('is_correct', true);
    }

    public function test_result_service_formats_multiple_choice_analysis_with_selected_and_correct_answers(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $quiz = Quiz::create([
            'title' => 'Math Quiz',
            'description' => null,
            'category_id' => null,
            'duration_minutes' => 30,
            'passing_score' => 50,
            'is_random_questions' => false,
            'is_published' => true,
            'scheduled_at' => now()->subMinute(),
            'ends_at' => now()->addMinutes(30),
            'max_attempts' => 1,
            'created_by' => $user->id,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Select all prime numbers.',
            'question_type' => 'multiple_choice',
            'points' => 10,
            'time_seconds' => 30,
            'order' => 1,
            'show_answer' => true,
            'created_by' => $user->id,
        ]);

        $optionTwo = Option::create([
            'question_id' => $question->id,
            'option_text' => '2',
            'is_correct' => true,
            'order' => 1,
        ]);

        $optionThree = Option::create([
            'question_id' => $question->id,
            'option_text' => '3',
            'is_correct' => true,
            'order' => 2,
        ]);

        $optionFour = Option::create([
            'question_id' => $question->id,
            'option_text' => '4',
            'is_correct' => false,
            'order' => 3,
        ]);

        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'participant_id' => null,
            'quiz_id' => $quiz->id,
            'score' => 0,
            'total_points' => 10,
            'correct_answers' => 0,
            'incorrect_answers' => 1,
            'total_questions' => 1,
            'started_at' => now()->subMinute(),
            'ended_at' => now(),
            'status' => 'completed',
            'ip_address' => '127.0.0.1',
        ]);

        UserAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'option_id' => null,
            'answer_text' => json_encode([$optionTwo->id, $optionFour->id]),
            'is_correct' => false,
            'points_earned' => 0,
            'time_taken_seconds' => 12,
        ]);

        $details = app(ResultService::class)->getDetailedResultForAttempt($attempt->fresh(['quiz', 'result']));

        $this->assertSame('2, 4', $details['answers'][0]['user_answer']);
        $this->assertSame('2, 3', $details['answers'][0]['correct_answer']);
        $this->assertSame([$optionTwo->id, $optionFour->id], $details['result']->question_wise_analysis[0]['selected_option_ids']);
        $this->assertSame([$optionTwo->id, $optionThree->id], $details['result']->question_wise_analysis[0]['correct_option_ids']);
    }
}
