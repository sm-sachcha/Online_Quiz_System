<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewSecurePass123!', $user->fresh()->password));
    }

    public function test_reset_form_allows_manual_email_entry_when_query_is_missing(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'sample-token']));

        $response->assertOk();
        $response->assertSee('name="email"', false);
        $response->assertDontSee('readonly');
        $response->assertSee('Enter the email address for the account you want to reset.');
    }
}
