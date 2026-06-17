<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SupportCallController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/users', [UserController::class, 'index']);
Route::get('/support-calls', [SupportCallController::class, 'index']);
Route::post('/support-calls', [SupportCallController::class, 'store']);
Route::get('/support-calls/{supportCall}', [SupportCallController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/support-calls/{supportCall}', [SupportCallController::class, 'update']);
    Route::patch('/support-calls/{supportCall}', [SupportCallController::class, 'update']);
    Route::delete('/support-calls/{supportCall}', [SupportCallController::class, 'destroy']);
});
