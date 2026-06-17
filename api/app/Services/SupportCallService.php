<?php

namespace App\Services;

use App\Helpers\ListQueryHelper;
use App\Models\SupportCall;
use App\Models\User;
use App\Repositories\Contracts\SupportCallRepositoryInterface;
use App\Services\Contracts\SupportCallServiceInterface;
use Illuminate\Validation\ValidationException;

class SupportCallService implements SupportCallServiceInterface
{
    private const DEFAULT_STATUS = 'open';
    private const ALLOWED_STATUS_TRANSITIONS = [
        'open' => ['in_progress', 'closed'],
        'in_progress' => ['resolved', 'closed'],
        'resolved' => [],
        'closed' => [],
    ];

    public function __construct(
        private readonly SupportCallRepositoryInterface $supportCallRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function listSupportCalls(array $filters): array
    {
        $sortBy = ListQueryHelper::normalizeSortBy(
            $filters['sort_by'] ?? null,
            ['created_at', 'status'],
            'created_at',
        );
        $sortDirection = ListQueryHelper::normalizeSortDirection($filters['sort_direction'] ?? null);
        $perPage = ListQueryHelper::normalizePerPage($filters['per_page'] ?? null);

        $paginator = $this->supportCallRepository->paginateWithResponsibleUser($sortBy, $sortDirection, $perPage);
        $paginator->through(fn (SupportCall $supportCall): array => $this->mapSupportCall($supportCall));

        /** @var array<string, mixed> $payload */
        $payload = $paginator->toArray();

        return $payload;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createSupportCall(array $data): array
    {
        $payload = $this->buildCreatePayload($data);
        $supportCall = $this->supportCallRepository->create($payload);

        return $this->mapSupportCall(
            $this->supportCallRepository->findByIdWithResponsibleUser($supportCall->id),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function showSupportCall(int $id): array
    {
        return $this->mapSupportCall(
            $this->supportCallRepository->findByIdWithResponsibleUser($id),
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateSupportCall(int $id, array $data): array
    {
        $supportCall = $this->supportCallRepository->findByIdWithResponsibleUser($id);
        $payload = $this->buildUpdatePayload($supportCall, $data);

        return $this->mapSupportCall(
            $this->supportCallRepository->update($supportCall, $payload),
        );
    }

    public function deleteSupportCall(int $id): void
    {
        $supportCall = $this->supportCallRepository->findByIdWithResponsibleUser($id);

        $this->supportCallRepository->delete($supportCall);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildCreatePayload(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => self::DEFAULT_STATUS,
            'responsible_user_id' => $this->resolveResponsibleUserId($data),
            'opened_at' => now(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildUpdatePayload(SupportCall $supportCall, array $data): array
    {
        $payload = [];

        foreach (['title', 'description', 'priority'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (array_key_exists('status', $data)) {
            $this->ensureStatusTransitionIsAllowed($supportCall->status, (string) $data['status']);
            $payload['status'] = $data['status'];
        }

        if (array_key_exists('responsible_user_id', $data)) {
            $payload['responsible_user_id'] = $data['responsible_user_id'];
        }

        return $payload;
    }

    private function ensureStatusTransitionIsAllowed(string $currentStatus, string $nextStatus): void
    {
        if ($currentStatus === $nextStatus) {
            return;
        }

        $allowedStatuses = self::ALLOWED_STATUS_TRANSITIONS[$currentStatus] ?? [];

        if (in_array($nextStatus, $allowedStatuses, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'A mudanca de status informada nao e permitida.',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveResponsibleUserId(array $data): int
    {
        if (array_key_exists('responsible_user_id', $data) && $data['responsible_user_id'] !== null) {
            return (int) $data['responsible_user_id'];
        }

        $user = $this->supportCallRepository->findUserWithFewestSupportCalls();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'responsible_user_id' => 'Nao ha responsaveis disponiveis para atribuicao automatica.',
            ]);
        }

        return $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSupportCall(SupportCall $supportCall): array
    {
        $responsibleUser = $supportCall->responsibleUser;

        return [
            'id' => $supportCall->id,
            'title' => $supportCall->title,
            'description' => $supportCall->description,
            'priority' => $supportCall->priority,
            'status' => $supportCall->status,
            'opened_at' => $supportCall->opened_at?->toJSON(),
            'created_at' => $supportCall->created_at?->toJSON(),
            'updated_at' => $supportCall->updated_at?->toJSON(),
            'responsible_user' => $responsibleUser instanceof User
                ? [
                    'id' => $responsibleUser->id,
                    'name' => $responsibleUser->name,
                    'email' => $responsibleUser->email,
                ]
                : null,
        ];
    }
}
