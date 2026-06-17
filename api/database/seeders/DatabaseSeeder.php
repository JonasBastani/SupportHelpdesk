<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Ana Souza',
                'email' => 'ana.souza@example.com',
            ],
            [
                'name' => 'Bruno Lima',
                'email' => 'bruno.lima@example.com',
            ],
            [
                'name' => 'Carla Mendes',
                'email' => 'carla.mendes@example.com',
            ],
            [
                'name' => 'Diego Santos',
                'email' => 'diego.santos@example.com',
            ],
            [
                'name' => 'Fernanda Rocha',
                'email' => 'fernanda.rocha@example.com',
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'Password123!',
                ],
            );
        }
    }
}
