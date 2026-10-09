<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:patient')->group(function () {
        Route::post('/food-log', [PatientController::class, 'storeFood']);
        Route::post('/symptom-log', [PatientController::class, 'storeSymptom']);
        Route::post('/sleep-log', [PatientController::class, 'storeSleep']);

        // Corrections: patients can fix or remove their own entries.
        Route::put('/food-log/{foodLog}', [PatientController::class, 'updateFood']);
        Route::delete('/food-log/{foodLog}', [PatientController::class, 'destroyFood']);
        Route::put('/symptom-log/{symptomLog}', [PatientController::class, 'updateSymptom']);
        Route::delete('/symptom-log/{symptomLog}', [PatientController::class, 'destroySymptom']);
        Route::put('/sleep-log/{sleepLog}', [PatientController::class, 'updateSleep']);
        Route::delete('/sleep-log/{sleepLog}', [PatientController::class, 'destroySleep']);
        Route::get('/insights', [PatientController::class, 'insights']);
        Route::get('/recommendations', [PatientController::class, 'recommendations']);
        Route::get('/history', [PatientController::class, 'history']);

        // Paginated, date-filterable views for clients that do not need the full timeline.
        Route::get('/food-logs', [PatientController::class, 'foodLogs']);
        Route::get('/symptom-logs', [PatientController::class, 'symptomLogs']);
        Route::get('/sleep-logs', [PatientController::class, 'sleepLogs']);
    });

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/analytics', [AdminController::class, 'analytics']);
        Route::get('/logs', [AdminController::class, 'systemLogs']);
        Route::post('/food-categories', [AdminController::class, 'storeCategory']);
        Route::put('/food-categories/{foodCategory}', [AdminController::class, 'updateCategory']);
        Route::delete('/food-categories/{foodCategory}', [AdminController::class, 'deleteCategory']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
    });
});
