<?php

namespace App\Repositories;

use App\Models\SupportCall;
use App\Models\User;
use App\Repositories\Contracts\SupportCallRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentSupportCallRepository implements SupportCallRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): SupportCall
    {
        return SupportCall::query()->create($data);
    }

    public function findByIdWithResponsibleUser(int $id): SupportCall
    {
        return SupportCall::query()
            ->with('responsibleUser')
            ->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(SupportCall $supportCall, array $data): SupportCall
    {
        $supportCall->fill($data);
        $supportCall->save();

        return $supportCall->fresh('responsibleUser');
    }

    public function delete(SupportCall $supportCall): void
    {
        $supportCall->delete();
    }

    public function paginateWithResponsibleUser(string $sortBy, string $sortDirection, int $perPage): LengthAwarePaginator
    {
        $query = SupportCall::query()->with('responsibleUser');

        if ($sortBy === 'status') {
            $query->orderByRaw("CAST(status AS CHAR) {$sortDirection}");
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query
            ->orderBy('id', $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findUserWithFewestSupportCalls(): ?User
    {
        return User::query()
            ->withCount('supportCalls')
            ->orderBy('support_calls_count')
            ->orderBy('id')
            ->first();
    }
}
