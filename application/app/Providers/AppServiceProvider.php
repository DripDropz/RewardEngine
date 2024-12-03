<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Discord\Provider as DiscordProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

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
        // RateLimiter::for('api-auth', fn() => Limit::perMinute(60));

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('discord', DiscordProvider::class);
        });

        // Log all SQL queries (with bindings) to file in local dev environment
        if (app()->environment('local')) {
            DB::listen(function ($query) {
                File::append(
                    storage_path('/logs/query.log'),
                    sprintf(
                        "[%s]\nQuery: %s\nBindings: [%s]\nTiming: %s milliseconds\n\n",
                        date('Y-m-d H:i:s'),
                        trim(preg_replace('/\s+/', ' ', $query->sql)),
                        implode(', ', $query->bindings),
                        $query->time,
                    ),
                );
            });
        }
    }
}
