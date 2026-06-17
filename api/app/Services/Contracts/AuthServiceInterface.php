<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Http\Request;

interface AuthServiceInterface
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function register(array $data): array;

    /**
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     */
    public function login(array $credentials): array;

    public function me(Request $request): User;

    public function logout(Request $request): void;
}
