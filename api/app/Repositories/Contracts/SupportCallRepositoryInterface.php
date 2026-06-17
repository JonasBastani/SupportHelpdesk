<?php

namespace App\Repositories\Contracts;

use App\Models\SupportCall;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupportCallRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): SupportCall;

    public function findByIdWithResponsibleUser(int $id): SupportCall;

    /**
     * @param array<string, mixed> $data
     */
    public function update(SupportCall $supportCall, array $data): SupportCall;

    public function delete(SupportCall $supportCall): void;

    public function paginateWithResponsibleUser(string $sortBy, string $sortDirection, int $perPage): LengthAwarePaginator;

    public function findUserWithFewestSupportCalls(): ?User;
}
