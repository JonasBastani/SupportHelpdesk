<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupportCalls\StoreSupportCallRequest;
use App\Http\Requests\SupportCalls\UpdateSupportCallRequest;
use App\Models\SupportCall;
use App\Services\Contracts\SupportCallServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportCallController extends Controller
{
    public function __construct(
        private readonly SupportCallServiceInterface $supportCallService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->supportCallService->listSupportCalls($request->query()),
        );
    }

    public function store(StoreSupportCallRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Chamado criado com sucesso.',
            'data' => $this->supportCallService->createSupportCall($request->validated()),
        ], 201);
    }

    public function show(SupportCall $supportCall): JsonResponse
    {
        return response()->json([
            'data' => $this->supportCallService->showSupportCall($supportCall->id),
        ]);
    }

    public function update(UpdateSupportCallRequest $request, SupportCall $supportCall): JsonResponse
    {
        return response()->json([
            'message' => 'Chamado atualizado com sucesso.',
            'data' => $this->supportCallService->updateSupportCall($supportCall->id, $request->validated()),
        ]);
    }

    public function destroy(SupportCall $supportCall): JsonResponse
    {
        $this->supportCallService->deleteSupportCall($supportCall->id);

        return response()->json([
            'message' => 'Chamado removido com sucesso.',
        ]);
    }
}
