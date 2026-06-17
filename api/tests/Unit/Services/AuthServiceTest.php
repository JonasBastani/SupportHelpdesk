<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user_during_registration(): void
    {
        $user = User::factory()->create([
            'name' => 'Ana Souza',
            'email' => 'ana.souza@example.com',
            'password' => 'Password123!',
        ]);

        $repository = Mockery::mock(UserRepositoryInterface::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('create')
                ->once()
                ->with([
                    'name' => 'Ana Souza',
                    'email' => 'ana.souza@example.com',
                    'password' => 'Password123!',
                ])
                ->andReturn($user);
        });

        $service = new AuthService($repository);

        $result = $service->register([
            'name' => 'Ana Souza',
            'email' => 'ana.souza@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertSame('ana.souza@example.com', $result['user']->email);
        $this->assertIsString($result['token']);
    }

    public function test_it_throws_when_login_credentials_are_invalid(): void
    {
        $user = User::factory()->create([
            'name' => 'Ana Souza',
            'email' => 'ana.souza@example.com',
            'password' => 'Password123!',
        ]);

        $repository = Mockery::mock(UserRepositoryInterface::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('findByEmail')
                ->once()
                ->with('ana.souza@example.com')
                ->andReturn($user);
        });

        $service = new AuthService($repository);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Email ou senha invalidos.');

        $service->login([
            'email' => 'ana.souza@example.com',
            'password' => 'WrongPassword123!',
        ]);
    }

    public function test_it_returns_the_authenticated_user_from_the_request(): void
    {
        $user = new User([
            'name' => 'Ana Souza',
            'email' => 'ana.souza@example.com',
        ]);

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $service = new AuthService($repository);

        $request = Request::create('/api/me', 'GET');
        $request->setUserResolver(fn (): User => $user);

        $result = $service->me($request);

        $this->assertSame($user, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
