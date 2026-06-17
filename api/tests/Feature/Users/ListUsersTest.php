<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_users_without_authentication(): void
    {
        $carlos = User::factory()->create([
            'name' => 'Carlos Lima',
            'email' => 'carlos.lima@example.com',
        ]);

        $amanda = User::factory()->create([
            'name' => 'Amanda Costa',
            'email' => 'amanda.costa@example.com',
        ]);

        $response = $this->getJson('/api/users');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertExactJson([
                'data' => [
                    [
                        'id' => $amanda->id,
                        'name' => 'Amanda Costa',
                        'email' => 'amanda.costa@example.com',
                    ],
                    [
                        'id' => $carlos->id,
                        'name' => 'Carlos Lima',
                        'email' => 'carlos.lima@example.com',
                    ],
                ],
            ]);
    }

    public function test_it_does_not_expose_sensitive_or_internal_fields(): void
    {
        User::factory()->create([
            'name' => 'Ana Souza',
            'email' => 'ana.souza@example.com',
        ]);

        $response = $this->getJson('/api/users');

        $response
            ->assertOk()
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.remember_token')
            ->assertJsonMissingPath('data.0.created_at')
            ->assertJsonMissingPath('data.0.updated_at');
    }
}
