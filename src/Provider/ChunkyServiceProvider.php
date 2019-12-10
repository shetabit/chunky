<?php

namespace Shetabit\Chunky\Provider;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class ChunkyServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Configurations that needs to be done by user.
         */
        $this->publishes(
            [
                __DIR__.'/../../config/Chunky.php' => config_path('chunky.php'),
            ],
            'config'
        );

        /**
         * Migrations that needs to be done by user.
         */
        $this->publishes(
            [
                __DIR__.'/../../database/migrations/' => database_path('migrations')
            ],
            'migrations'
        );

        $this->registerMacroHelpers();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Load default configurations.
         */
        $this->mergeConfigFrom(
            __DIR__.'/../../config/chunky.php', 'visitor'
        );

        /**
         * Bind to service container.
         */
        $this->app->singleton('shetabit-chunky', function () {
            $request = app(Request::class);

            // return new Chunky($request, config('chunky'));
        });
    }

    /**
     * Register micros
     */
    protected function registerMacroHelpers()
    {
        Request::macro('chunky', function () {
            return app('shetabit-chunky');
        });

        Response::macro('chunky', function () {
            return app('shetabit-chunky');
        });
    }
}
