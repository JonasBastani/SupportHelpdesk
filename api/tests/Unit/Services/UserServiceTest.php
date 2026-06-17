<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    public function test_it_returns_only_the_public_user_fields(): void
    {
        $users = new Collection([
            (new User([
                'name' => 'Bruno Lima',
                'email' => 'bruno.lima@example.com',
                'password' => 'Password123!',
            ]))->forceFill([
                'id' => 2,
            ]),
            (new User([
                'name' => 'Ana Souza',
                'email' => 'ana.souza@example.com',
                'password' => 'Password123!',
            ]))->forceFill([
                'id' => 1,
            ]),
        ]);

        $repository = Mockery::mock(UserRepositoryInterface::class, function (MockInterface $mock) use ($users): void {
            $mock->shouldReceive('getAllOrderedByName')
                ->once()
                ->andReturn($users);
        });

        $service = new UserService($repository);

        $result = $service->listUsers();

        $this->assertSame([
            [
                'id' => 2,
                'name' => 'Bruno Lima',
                'email' => 'bruno.lima@example.com',
            ],
            [
                'id' => 1,
                'name' => 'Ana Souza',
                'email' => 'ana.souza@example.com',
            ],
        ], $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
