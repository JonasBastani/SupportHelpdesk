<?php

namespace App\Services\Contracts;

interface UserServiceInterface
{
    /**
     * @return array<int, array{id: int, name: string, email: string}>
     */
    public function listUsers(): array;
}
