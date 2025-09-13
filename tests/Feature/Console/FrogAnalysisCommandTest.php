<?php

declare(strict_types=1);

namespace FrogUri\Test\Feature\Console;

use FrogUri\FrogUriServiceProvider;
use Illuminate\Support\Facades\Process;
use Orchestra\Testbench\TestCase;

final class FrogAnalysisCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Process facade for testing
        Process::fake();
    }

    public function test_frog_analyze_command_is_registered(): void
    {
        // Mock the process to avoid actual execution
        Process::fake([
            'php artisan route:list -vv --json' => Process::result(
                output: '[]',
                exitCode: 0
            ),
        ]);

        $this->artisan('frog:analyze')
            ->assertExitCode(0);
    }

    public function test_frog_analyze_with_danger_option_is_registered(): void
    {
        // Mock the process to avoid actual execution
        Process::fake([
            'php artisan route:list -vv --json' => Process::result(
                output: '[]',
                exitCode: 0
            ),
        ]);

        $this->artisan('frog:analyze --danger')
            ->assertExitCode(0);
    }

    public function test_command_handles_process_failure_gracefully(): void
    {
        Process::fake([
            'php artisan route:list -vv --json' => Process::result(
                output: '',
                errorOutput: 'Command failed',
                exitCode: 1
            ),
        ]);

        $this->artisan('frog:analyze')
            ->assertExitCode(0);
    }

    public function test_process_assertion_verifies_correct_command(): void
    {
        Process::fake([
            'php artisan route:list -vv --json' => Process::result(
                output: '[]',
                exitCode: 0
            ),
        ]);

        $this->artisan('frog:analyze');

        Process::assertRan('php artisan route:list -vv --json');
    }

    protected function getPackageProviders($app): array
    {
        return [
            FrogUriServiceProvider::class,
        ];
    }
}
