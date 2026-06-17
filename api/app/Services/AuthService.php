<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function register(array $data): array
    {
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return $this->buildAuthPayload($user, 'registration_token');
    }

    /**
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     */
    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user instanceof User || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Email ou senha invalidos.');
        }

        return $this->buildAuthPayload($user, 'auth_token');
    }

    public function me(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    public function logout(Request $request): void
    {
        $request->user()?->currentAccessToken()?->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAuthPayload(User $user, string $tokenName): array
    {
        return [
            'token' => $user->createToken($tokenName)->plainTextToken,
            'user' => $user->only(['id', 'name', 'email'])
        ];
    }
}
