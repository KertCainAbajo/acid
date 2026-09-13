<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogPageController;
use App\Http\Controllers\PatientSectionController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check() ? redirect()->route('dashboard') : view('auth.login'))->name('home');
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.submit');
Route::post('/register', [WebAuthController::class, 'register'])->name('register.submit');
Route::get('/auth/google/redirect', [WebAuthController::class, 'googleRedirect'])->name('google.redirect');
Route::get('/auth/google/callback', [WebAuthController::class, 'googleCallback'])->name('google.callback');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/food-log', [LogPageController::class, 'food'])->name('food.log');
    Route::get('/symptom-log', [LogPageController::class, 'symptom'])->name('symptom.log');
    Route::get('/sleep-stress', [LogPageController::class, 'sleep'])->name('sleep.log');
    Route::get('/insights', [PatientSectionController::class, 'insights'])->name('insights');
    Route::get('/recommendations', [PatientSectionController::class, 'recommendations'])->name('recommendations');
    Route::get('/health-history', [PatientSectionController::class, 'history'])->name('history');
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});
