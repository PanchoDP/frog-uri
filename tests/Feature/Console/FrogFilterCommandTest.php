<?php

declare(strict_types=1);

namespace FrogUri\Test\Feature\Console;

use FrogUri\FrogUriServiceProvider;
use Illuminate\Support\Facades\Process;
use Orchestra\Testbench\TestCase;

final class FrogFilterCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
    }

    public function test_frog_filter_command_is_registered(): void
    {
        Process::fake([
            'php artisan route:list -vv --json' => Process::result(
                output: '[]',
                exitCode: 0
            ),
        ]);

        $this->artisan('frog:filter')
            ->assertExitCode(1);
    }

    public function test_command_processes_failure_gracefully(): void
    {
        Process::fake([
            'php artisan route:list -vv --json' => Process::result(
                output: '',
                errorOutput: 'Command failed',
                exitCode: 1
            ),
        ]);

        $this->artisan('frog:filter')
            ->assertExitCode(1);
    }

    public function test_exclude_flag_is_recognized(): void
    {
        Process::fake([
            'php artisan route:list -vv --json' => Process::result(
                output: '[]',
                exitCode: 0
            ),
        ]);

        $this->artisan('frog:filter --exclude')
            ->assertExitCode(1);
    }

    public function test_process_assertion_verifies_correct_command(): void
    {
        Process::fake([
            'php artisan route:list -vv --json' => Process::result(
                output: '[]',
                exitCode: 0
            ),
        ]);

        $this->artisan('frog:filter');

        Process::assertRan('php artisan route:list -vv --json');
    }

    protected function getPackageProviders($app): array
    {
        return [
            FrogUriServiceProvider::class,
        ];
    }
}
