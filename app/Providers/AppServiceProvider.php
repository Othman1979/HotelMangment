<?php

namespace App\Providers;

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
        $this->usePublicUrlBehindProxy();
    }

    private function usePublicUrlBehindProxy(): void
    {
        $root = config('app.public_url');
        if (! $root || $this->app->runningInConsole()) {
            return;
        }

        $request = $this->app['request'];
        $direct = in_array($request->getHost(), ['localhost', '127.0.0.1'], true) && ! $request->headers->has('X-Forwarded-For');
        if (! $direct) {
            URL::forceRootUrl(rtrim($root, '/'));
            URL::forceScheme(parse_url($root, PHP_URL_SCHEME) ?: 'https');
        }
    }
}
