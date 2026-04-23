<?php

namespace Tests\Unit\Models;

use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Models\User;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    public function test_display_name_uses_the_registered_users_name_when_available(): void
    {
        $attempt = new QuizAttempt(['user_id' => 1]);
        $attempt->setRelation('user', new User(['name' => 'Alice']));

        $this->assertSame('Alice', $attempt->display_name);
    }

    public function test_display_name_uses_the_guest_name_for_guest_attempts(): void
    {
        $attempt = new QuizAttempt(['participant_id' => 1]);
        $attempt->setRelation('participant', new QuizParticipant(['guest_name' => 'Guest Player']));

        $this->assertSame('Guest Player', $attempt->display_name);
    }

    public function test_display_name_falls_back_to_unknown_when_no_user_or_participant_is_available(): void
    {
        $attempt = new QuizAttempt();

        $this->assertSame('Unknown', $attempt->display_name);
    }
}
