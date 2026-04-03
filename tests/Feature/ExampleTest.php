<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_admin_quiz_participant_remove_route_uses_single_admin_prefix(): void
    {
        $this->assertTrue(Route::has('admin.quiz-participants.remove'));
        $this->assertSame(
            '/admin/quiz-participants/123/remove',
            route('admin.quiz-participants.remove', ['participant' => 123], false)
        );
    }
}
