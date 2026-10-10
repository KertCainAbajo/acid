<?php

use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminActivityController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminFoodCategoryController;
use App\Http\Controllers\AdminPatientController;
use App\Http\Controllers\AdminSecurityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogPageController;
use App\Http\Controllers\LogSearchController;
use App\Http\Controllers\PatientSectionController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check() ? redirect()->route('dashboard') : view('auth.login'))->name('home');
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');
Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');
Route::post('/register', [WebAuthController::class, 'register'])->middleware('throttle:register')->name('register.submit');
Route::get('/auth/google/redirect', [WebAuthController::class, 'googleRedirect'])->name('google.redirect');
Route::get('/auth/google/callback', [WebAuthController::class, 'googleCallback'])->name('google.callback');
Route::get('/two-factor-challenge', [WebAuthController::class, 'showTwoFactorChallenge'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [WebAuthController::class, 'verifyTwoFactorChallenge'])->middleware('throttle:6,1')->name('two-factor.verify');

Route::middleware(['auth', 'active', 'secure.session'])->group(function () {
    Route::prefix('security')->name('security.')->middleware('throttle:12,1')->group(function () {
        Route::post('/two-factor', [SecurityController::class, 'enableTwoFactor'])->name('two-factor.enable');
        Route::post('/two-factor/confirm', [SecurityController::class, 'confirmTwoFactor'])->name('two-factor.confirm');
        Route::delete('/two-factor', [SecurityController::class, 'disableTwoFactor'])->name('two-factor.disable');
        Route::post('/two-factor/recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery');
        Route::post('/sessions/sign-out-others', [SecurityController::class, 'signOutOtherSessions'])->name('sessions.destroy');
        Route::put('/password', [SecurityController::class, 'updatePassword'])->name('password');
    });

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/patients', [AdminPatientController::class, 'index'])->name('patients');
        Route::get('/patients/export', [AdminPatientController::class, 'export'])->name('patients.export');
        Route::patch('/patients/{patient}/status', [AdminPatientController::class, 'updateStatus'])->name('patients.status');
        Route::delete('/patients/{patient}', [AdminPatientController::class, 'destroy'])->name('patients.destroy');
        Route::get('/food-guidance', [AdminFoodCategoryController::class, 'index'])->name('food-guidance');
        Route::post('/food-guidance', [AdminFoodCategoryController::class, 'store'])->name('food-guidance.store');
        Route::put('/food-guidance/{foodCategory}', [AdminFoodCategoryController::class, 'update'])->name('food-guidance.update');
        Route::delete('/food-guidance/{foodCategory}', [AdminFoodCategoryController::class, 'destroy'])->name('food-guidance.destroy');
        Route::get('/activity', [AdminActivityController::class, 'index'])->name('activity');
        Route::get('/activity/export', [AdminActivityController::class, 'export'])->name('activity.export');
        Route::get('/settings', [AdminAccountController::class, 'edit'])->name('settings');
        Route::put('/settings/profile', [AdminAccountController::class, 'updateProfile'])->name('settings.profile');
        Route::put('/settings/password', [AdminAccountController::class, 'updatePassword'])->middleware('throttle:6,1')->name('settings.password');
        Route::get('/security', [AdminSecurityController::class, 'index'])->name('security');
        Route::post('/security/unlock/{user}', [AdminSecurityController::class, 'unlock'])->name('security.unlock');
    });
    Route::middleware('role:patient')->group(function () {
        Route::get('/food-log', [LogPageController::class, 'food'])->name('food.log');
        Route::get('/symptom-log', [LogPageController::class, 'symptom'])->name('symptom.log');
        Route::get('/sleep-stress', [LogPageController::class, 'sleep'])->name('sleep.log');
        Route::get('/search-logs', LogSearchController::class)->name('logs.search');
        Route::get('/insights', [PatientSectionController::class, 'insights'])->name('insights');
        Route::post('/insights/refresh', [PatientSectionController::class, 'refreshInsights'])->middleware('throttle:6,1')->name('insights.refresh');
        Route::get('/health-history/export', [PatientSectionController::class, 'exportHistory'])->name('history.export');
        Route::get('/recommendations', [PatientSectionController::class, 'recommendations'])->name('recommendations');
        Route::get('/health-history', [PatientSectionController::class, 'history'])->name('history');
        Route::get('/account/security', [SecurityController::class, 'show'])->name('security.show');
    });
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});
