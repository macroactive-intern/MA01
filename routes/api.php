<?php

use App\Http\Controllers\Api\ExerciseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('exercises', [ExerciseController::class, 'index']);
    Route::get('exercises/{exercise}', [ExerciseController::class, 'show']);
    Route::post('exercises', [ExerciseController::class, 'store']);
    Route::put('exercises/{exercise}', [ExerciseController::class, 'update']);
    Route::delete('exercises/{exercise}', [ExerciseController::class, 'destroy']);
});
