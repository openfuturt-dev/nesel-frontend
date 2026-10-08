<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        URL::forceHttps($this->app->isProduction());

        // Keyed on the client IP, which only honours X-Forwarded-For from the
        // proxies listed in config/trustedproxy.php (none by default).
        RateLimiter::for('contact-requests', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn (Request $request, array $headers) => redirect()
                ->to(route('home').'#contact')
                ->withHeaders($headers)
                ->withInput($request->except('_token'))
                ->withErrors(['throttle' => 'Plusieurs envois ont été effectués en peu de temps. Veuillez patienter une minute avant de réessayer.'])));
    }
}
