<?php

namespace App\Providers;

use App\View\Composers\AdminSidebarComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        View::composer('admin.partials.sidebar', AdminSidebarComposer::class);
    }

    /**
     * Limit authentication attempts so passwords cannot be brute-forced.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::transliterate(Str::lower((string) $request->input('email'))).'|'.$request->ip())
            ->response(fn (Request $request, array $headers): Response => $this->tooManyAttempts($request, $headers, 'email')));

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn (Request $request, array $headers): Response => $this->tooManyAttempts($request, $headers, 'email')));
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function tooManyAttempts(Request $request, array $headers, string $field): Response
    {
        $seconds = (int) ($headers['Retry-After'] ?? 60);
        $message = "Too many attempts. Please try again in {$seconds} seconds.";

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], 429, $headers);
        }

        return back()->withInput($request->except('password', 'password_confirmation'))->withErrors([$field => $message]);
    }
}
