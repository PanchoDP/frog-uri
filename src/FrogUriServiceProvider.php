<?php

declare(strict_types=1);

namespace FrogUri;

use FrogUri\Console\FrogAnalysisCommand;
use FrogUri\Console\FrogFilterCommand;
use Illuminate\Support\ServiceProvider;

final class FrogUriServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FrogAnalysisCommand::class,
                FrogFilterCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->publishes([
            __DIR__.'/../resources/static/css/frog-style.css' => public_path('vendor/frog-uri/css/frog-style.css'),
            __DIR__.'/../resources/static/js/frog-js.js' => public_path('vendor/frog-uri/js/frog-js.js'),
        ], 'frog-uri-assets');

        $this->mergeConfigFrom(
            __DIR__.'/../config/frog-uri.php',
            'frog-uri-config'
        );
    }
}
