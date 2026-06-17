<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): User;

    public function findByEmail(string $email): ?User;

    /**
     * @return Collection<int, User>
     */
    public function getAllOrderedByName(): Collection;
}
