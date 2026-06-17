<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_in_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'ana.souza@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ana.souza@example.com',
            'password' => 'Password123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login realizado com sucesso.')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_it_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'ana.souza@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ana.souza@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email ou senha invalidos.');
    }

    public function test_it_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_it_requires_authentication_to_return_the_authenticated_user(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }

    public function test_it_logs_out_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Logout realizado com sucesso.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
