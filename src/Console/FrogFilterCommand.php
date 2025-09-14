<?php

declare(strict_types=1);

namespace FrogUri\Console;

use FrogUri\Actions\FilterMiddlewaresUriAction;
use FrogUri\Actions\GetJsonAction;
use FrogUri\Actions\MappingAction;
use FrogUri\Actions\RenderInformAction;
use Illuminate\Console\Command;

final class FrogFilterCommand extends Command
{
    protected $signature = 'frog:filter {--exclude : Show routes that do NOT have the selected middlewares}';

    protected $description = 'Filter routes based on middleware using an interactive interface';

    public function handle(): int
    {
        $excludeMode = (bool) $this->option('exclude');

        $this->info('🐸 Frog URI Interactive Filter');
        if ($excludeMode) {
            $this->info('🚫 Exclude Mode: Finding routes WITHOUT selected middlewares');
        }
        $this->newLine();

        $this->info('Capturing route information...');
        $jsonData = GetJsonAction::handle();

        if (empty($jsonData)) {
            $this->error('❌ No route data found. Make sure you have routes defined in your Laravel application.');

            return Command::FAILURE;
        }

        $this->info('Processing data...');
        $collection = MappingAction::handle($jsonData);

        $this->info('Total routes found: '.$collection->count());
        $this->newLine();

        $availableMiddlewares = $collection->getAllMiddlewares();

        if (empty($availableMiddlewares)) {
            $this->warn('⚠️  No middlewares found in your routes.');
            $this->info('Showing all routes:');
            $this->newLine();
            RenderInformAction::handle($collection, $this);

            return Command::SUCCESS;
        }

        $selectedMiddlewares = $this->selectMiddlewares($availableMiddlewares, $excludeMode);

        if (empty($selectedMiddlewares)) {
            $this->info('No middlewares selected. Showing all routes:');
            $this->newLine();
            RenderInformAction::handle($collection, $this);

            return Command::SUCCESS;
        }

        $this->info('Applying filters...');
        $filteredCollection = FilterMiddlewaresUriAction::handle($collection, $selectedMiddlewares, $excludeMode);

        $this->newLine();
        $this->info('🎯 Filtered Results');
        $mode = $excludeMode ? 'excluding' : 'including';
        $this->info("Filter mode: {$mode} middlewares: ".implode(', ', $selectedMiddlewares));
        $this->info('Routes found: '.$filteredCollection->count());
        $this->newLine();

        if ($filteredCollection->count() === 0) {
            $message = $excludeMode
                ? '❌ No routes found that exclude the selected middlewares.'
                : '❌ No routes found with the selected middlewares.';
            $this->warn($message);

            return Command::SUCCESS;
        }

        RenderInformAction::handle($filteredCollection, $this);

        return Command::SUCCESS;
    }

    /**
     * @param  array<int, string>  $availableMiddlewares
     * @return array<int, string>
     */
    private function selectMiddlewares(array $availableMiddlewares, bool $excludeMode = false): array
    {
        $this->info('📋 Available Middlewares:');
        $this->newLine();

        $middlewareOptions = $this->prepareMiddlewareTable($availableMiddlewares);
        $this->table(['#', 'Middleware Name'], $middlewareOptions);

        $this->newLine();
        $this->info('💡 Instructions:');
        $this->line('• Enter middleware numbers separated by commas (e.g., 1,3,5)');
        $this->line('• Enter "all" to select all middlewares');
        $this->line('• Press Enter without input to show all routes');
        if ($excludeMode) {
            $this->line('');
            $this->warn('🚫 EXCLUDE MODE: Selected middlewares will be EXCLUDED from results');
            $this->warn('   This will show routes that do NOT contain the selected middlewares');
        }
        $this->newLine();

        $promptText = $excludeMode
            ? 'Select middlewares to EXCLUDE by number'
            : 'Select middlewares by number';

        $selection = $this->ask($promptText);

        return $this->processSelection(is_string($selection) ? $selection : null, $availableMiddlewares);
    }

    /**
     * @param  array<int, string>  $middlewares
     * @return array<int, array<string, mixed>>
     */
    private function prepareMiddlewareTable(array $middlewares): array
    {
        $table = [];
        foreach ($middlewares as $index => $middleware) {
            $table[] = [
                'number' => $index + 1,
                'name' => $this->sanitizeForDisplay($middleware),
            ];
        }

        return $table;
    }

    /**
     * @param  array<int, string>  $availableMiddlewares
     * @return array<int, string>
     */
    private function processSelection(?string $selection, array $availableMiddlewares): array
    {
        if (empty($selection)) {
            return [];
        }

        $selection = mb_trim($selection);

        if (mb_strtolower($selection) === 'all') {
            return $availableMiddlewares;
        }

        $selectedNumbers = array_map('trim', explode(',', $selection));
        $selectedMiddlewares = [];

        foreach ($selectedNumbers as $numberStr) {
            if (! ctype_digit($numberStr)) {
                continue;
            }

            $number = (int) $numberStr;
            $index = $number - 1;

            if (isset($availableMiddlewares[$index])) {
                $selectedMiddlewares[] = $availableMiddlewares[$index];
            }
        }

        return array_unique($selectedMiddlewares);
    }

    private function sanitizeForDisplay(string $data): string
    {
        $cleanData = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
        $data = $cleanData ?? '';

        if (mb_strlen($data) > 50) {
            $data = mb_substr($data, 0, 47).'...';
        }

        return mb_trim($data);
    }
}
