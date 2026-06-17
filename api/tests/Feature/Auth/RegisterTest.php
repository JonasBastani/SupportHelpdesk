<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_responsible_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Maria Oliveira',
            'email' => 'maria.oliveira@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Responsavel cadastrado com sucesso.')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'maria.oliveira@example.com',
        ]);
    }

    public function test_it_rejects_duplicate_email_registration(): void
    {
        User::factory()->create([
            'email' => 'maria.oliveira@example.com',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Maria Oliveira',
            'email' => 'maria.oliveira@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
