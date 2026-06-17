<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_default_responsible_users_without_duplicates(): void
    {
        Artisan::call('db:seed');
        Artisan::call('db:seed');

        $expectedEmails = [
            'ana.souza@example.com',
            'bruno.lima@example.com',
            'carla.mendes@example.com',
            'diego.santos@example.com',
            'fernanda.rocha@example.com',
        ];

        $this->assertDatabaseCount('users', 5);

        foreach ($expectedEmails as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);
        }
    }
}
