<?php

namespace App\Http\Controllers;

use App\Services\Contracts\UserServiceInterface;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->userService->listUsers(),
        ]);
    }
}
