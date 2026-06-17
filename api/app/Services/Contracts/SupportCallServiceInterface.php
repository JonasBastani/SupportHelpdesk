<?php

namespace App\Services\Contracts;

interface SupportCallServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function listSupportCalls(array $filters): array;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createSupportCall(array $data): array;

    /**
     * @return array<string, mixed>
     */
    public function showSupportCall(int $id): array;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateSupportCall(int $id, array $data): array;

    public function deleteSupportCall(int $id): void;
}
