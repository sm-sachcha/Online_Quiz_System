<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Leaderboard;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ResultControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_the_latest_completed_attempt_per_participant_for_the_signed_in_admin(): void
    {
        $admin = $this->createAdmin('Admin Owner');
        $otherAdmin = $this->createAdmin('Other Admin');
        $participant = User::factory()->create([
            'name' => 'Student One',
            'role' => 'user',
        ]);

        $ownedQuiz = $this->createQuiz($admin, ['title' => 'Owned Quiz']);
        $otherQuiz = $this->createQuiz($otherAdmin, ['title' => 'Other Quiz']);

        $this->createCompletedAttempt($ownedQuiz, user: $participant, attributes: [
            'score' => 4,
            'correct_answers' => 2,
            'incorrect_answers' => 3,
            'total_questions' => 5,
        ], passed: false);

        $latestAttempt = $this->createCompletedAttempt($ownedQuiz, user: $participant, attributes: [
            'score' => 8,
            'correct_answers' => 4,
            'incorrect_answers' => 1,
            'total_questions' => 5,
        ], passed: true);

        $this->createCompletedAttempt($otherQuiz, user: User::factory()->create([
            'name' => 'Hidden Student',
            'role' => 'user',
        ]), attributes: [
            'score' => 9,
            'correct_answers' => 5,
            'incorrect_answers' => 0,
            'total_questions' => 5,
        ], passed: true);

        $response = $this->actingAs($admin)->get(route('admin.results.index'));

        $response->assertOk();
        $response->assertViewHas('attempts', function (LengthAwarePaginator $attempts) use ($latestAttempt) {
            $collection = $attempts->getCollection();

            return $collection->count() === 1
                && $collection->first()->id === $latestAttempt->id
                && $collection->first()->attempt_count === 2;
        });
        $response->assertViewHas('quizzes', fn ($quizzes) => $quizzes->pluck('id')->all() === [$ownedQuiz->id]);
    }

    public function test_index_returns_forbidden_when_an_admin_filters_results_for_another_admins_quiz(): void
    {
        $admin = $this->createAdmin('Current Admin');
        $otherAdmin = $this->createAdmin('Other Admin');
        $otherQuiz = $this->createQuiz($otherAdmin, ['title' => 'Restricted Quiz']);

        $response = $this->actingAs($admin)->get(route('admin.results.index', [
            'quiz_id' => $otherQuiz->id,
        ]));

        $response->assertForbidden();
    }

    public function test_show_exposes_guest_result_details_for_an_owned_quiz_attempt(): void
    {
        $admin = $this->createAdmin('Result Admin');
        $quiz = $this->createQuiz($admin, [
            'title' => 'Guest Quiz',
            'total_points' => 10,
        ]);

        $participant = QuizParticipant::create([
            'quiz_id' => $quiz->id,
            'user_id' => null,
            'session_id' => 'guest-session-1',
            'guest_name' => 'Walk-in Guest',
            'device_id' => 'device-1',
            'is_guest' => true,
            'status' => 'completed',
            'joined_at' => now()->subHour(),
            'left_at' => now()->subMinutes(5),
        ]);

        $this->createCompletedAttempt($quiz, participant: $participant, attributes: [
            'score' => 5,
            'correct_answers' => 2,
            'incorrect_answers' => 3,
            'total_questions' => 5,
        ], passed: false);

        $attempt = $this->createCompletedAttempt($quiz, participant: $participant, attributes: [
            'score' => 8,
            'correct_answers' => 4,
            'incorrect_answers' => 1,
            'total_questions' => 5,
        ], passed: true);

        Leaderboard::create([
            'quiz_id' => $quiz->id,
            'user_id' => null,
            'participant_id' => $participant->id,
            'score' => 8,
            'rank' => 1,
            'metadata' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.results.show', $attempt));

        $response->assertOk();
        $response->assertViewHas('attemptCount', 2);
        $response->assertViewHas('userRank', 1);
        $response->assertViewHas('totalParticipants', 1);
        $response->assertViewHas('percentage', 80.0);
        $response->assertViewHas('isGuest', true);
        $response->assertViewHas('userName', 'Walk-in Guest');
        $response->assertViewHas('userEmail', 'Guest User');
    }

    public function test_export_streams_only_passed_results_when_that_filter_is_requested(): void
    {
        $admin = $this->createAdmin('Export Admin');
        $quiz = $this->createQuiz($admin, ['title' => 'Monthly Exam']);

        $passedUser = User::factory()->create([
            'name' => 'Passed Student',
            'role' => 'user',
        ]);
        $failedUser = User::factory()->create([
            'name' => 'Failed Student',
            'role' => 'user',
        ]);

        $this->createCompletedAttempt($quiz, user: $passedUser, attributes: [
            'score' => 9,
            'correct_answers' => 4,
            'incorrect_answers' => 1,
            'total_questions' => 5,
        ], passed: true);

        $this->createCompletedAttempt($quiz, user: $failedUser, attributes: [
            'score' => 3,
            'correct_answers' => 1,
            'incorrect_answers' => 4,
            'total_questions' => 5,
        ], passed: false);

        Leaderboard::create([
            'quiz_id' => $quiz->id,
            'user_id' => $passedUser->id,
            'participant_id' => null,
            'score' => 9,
            'rank' => 1,
            'metadata' => null,
        ]);

        Leaderboard::create([
            'quiz_id' => $quiz->id,
            'user_id' => $failedUser->id,
            'participant_id' => null,
            'score' => 3,
            'rank' => 2,
            'metadata' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.results.export', [
            'quiz_id' => $quiz->id,
            'status' => 'passed',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type', ''));
        $response->assertHeader('Content-Disposition');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Passed Student', $csv);
        $this->assertStringContainsString('Passed', $csv);
        $this->assertStringNotContainsString('Failed Student', $csv);
        $this->assertStringNotContainsString('Failed,3,10', $csv);
    }

    private function createAdmin(string $name, string $role = 'admin'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => $role,
        ]);
    }

    private function createQuiz(User $admin, array $attributes = []): Quiz
    {
        $category = Category::create([
            'name' => ($attributes['title'] ?? 'Category') . ' Category',
            'description' => 'Test category',
            'icon' => 'fas fa-book',
            'color' => '#3366ff',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        return Quiz::create(array_merge([
            'title' => 'Quiz ' . uniqid(),
            'description' => 'Test quiz',
            'category_id' => $category->id,
            'duration_minutes' => 30,
            'total_questions' => 5,
            'passing_score' => 50,
            'is_random_questions' => false,
            'is_random_options' => false,
            'is_published' => true,
            'scheduled_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'max_attempts' => 3,
            'total_points' => 10,
            'settings' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ], $attributes));
    }

    private function createCompletedAttempt(
        Quiz $quiz,
        ?User $user = null,
        ?QuizParticipant $participant = null,
        array $attributes = [],
        bool $passed = true
    ): QuizAttempt {
        $attempt = QuizAttempt::create(array_merge([
            'user_id' => $user?->id,
            'participant_id' => $participant?->id,
            'quiz_id' => $quiz->id,
            'score' => 6,
            'total_points' => $quiz->total_points,
            'correct_answers' => 3,
            'incorrect_answers' => 2,
            'total_questions' => 5,
            'started_at' => now()->subMinutes(20),
            'ended_at' => now()->subMinutes(10),
            'status' => 'completed',
            'cheating_logs' => null,
            'ip_address' => '127.0.0.1',
        ], $attributes));

        QuizResult::create([
            'quiz_attempt_id' => $attempt->id,
            'total_score' => $attempt->score,
            'percentage' => $quiz->total_points > 0
                ? (int) round(($attempt->score / $quiz->total_points) * 100)
                : 0,
            'passed' => $passed,
            'rank' => null,
            'question_wise_analysis' => null,
            'time_analysis' => null,
        ]);

        return $attempt;
    }
}
