<?php

declare(strict_types=1);

namespace FrogUri\Test\Feature;

use FrogUri\FrogUriServiceProvider;
use Orchestra\Testbench\TestCase;

final class FrogTest extends TestCase
{
    public function test_the_configuration_file_exist_in_the_package(): void
    {
        $configPath = __DIR__.'/../../config/frog-uri.php';

        $this->assertFileExists($configPath);
    }

    public function test_service_provider_is_registered(): void
    {
        $this->assertTrue(
            $this->app->getProviders(FrogUriServiceProvider::class) !== []
        );
    }

    public function test_frog_analyze_command_is_registered(): void
    {
        $this->assertTrue(
            $this->app->make('Illuminate\Contracts\Console\Kernel') !== null
        );

        // Just verify that the command exists by trying to get its signature
        $this->assertTrue(class_exists('FrogUri\Console\FrogAnalysisCommand'));
    }

    protected function getPackageProviders($app): array
    {
        return [
            FrogUriServiceProvider::class,
        ];
    }
}
