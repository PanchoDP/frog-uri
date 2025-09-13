<?php

declare(strict_types=1);

namespace FrogUri\Console;

use FrogUri\Actions\GetJsonAction;
use FrogUri\Actions\MappingAction;
use FrogUri\Actions\RenderInformAction;
use FrogUri\Collections\RouteCollection;
use Illuminate\Console\Command;

final class FrogAnalysisCommand extends Command
{
    protected $signature = 'frog:analyze {--danger : Show only routes without middleware}';

    protected $description = 'Inspect and analysis the URLs';

    public function handle(): int
    {
        $this->info('Capturing Information!');
        $json_data = GetJsonAction::handle();
        $this->info('Processing Data!');
        $collection = MappingAction::handle($json_data);

        if ($this->option('danger')) {
            $dangerousRoutes = $collection->getRoutesWithoutMiddleware();
            $this->warn('⚠️  DANGEROUS ROUTES - Without protection middleware ⚠️');
            $this->info('Total routes without middleware: '.$dangerousRoutes->count());
            $this->renderDangerousRoutes($dangerousRoutes);
        } else {
            $this->info('Total routes: '.$collection->count());
            $this->info('Summary table!');
            RenderInformAction::handle($collection, $this);
        }

        $this->info('Process completed!');

        return Command::SUCCESS;
    }

    private function renderDangerousRoutes(RouteCollection $dangerousRoutes): void
    {
        if ($dangerousRoutes->count() === 0) {
            $this->info('✅ Perfect! No routes without middleware found.');

            return;
        }

        $this->newLine();
        $this->warn('The following routes have NO middleware and may be dangerous:');
        $this->newLine();

        $routes = $dangerousRoutes->toArray();

        foreach ($routes as $index => $route) {
            $methodData = $route['method'] ?? 'N/A';
            $uriData = $route['uri'] ?? 'N/A';
            $actionData = $route['action'] ?? 'N/A';
            $nameData = $route['name'] ?? 'No name';

            $method = mb_str_pad(is_string($methodData) ? $methodData : 'N/A', 12);
            $uri = mb_str_pad(is_string($uriData) ? $uriData : 'N/A', 40);
            $action = is_string($actionData) ? $actionData : 'N/A';
            $name = is_string($nameData) ? $nameData : 'No name';

            $this->line(sprintf(
                '<fg=red>%s</fg=red> <fg=yellow>%s</fg=yellow> <fg=white>%s</fg=white> <fg=gray>[%s]</fg=gray>',
                (string) $method,
                (string) $uri,
                (string) $action,
                (string) $name
            ));
        }

        $this->newLine();
        $this->warn('💡 Consider adding security middleware to these routes (auth, throttle, etc.)');
    }
}
