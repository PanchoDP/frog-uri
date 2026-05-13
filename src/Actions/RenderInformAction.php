<?php

declare(strict_types=1);

namespace FrogUri\Actions;

use FrogUri\Collections\RouteCollection;
use Illuminate\Console\Command;

final class RenderInformAction
{
    public static function handle(RouteCollection $collection, ?Command $command = null): void
    {
        $routesByMethod = self::groupRoutesByMethod($collection->toArray());

        $sortedRoutes = self::sortRoutesByMiddleware($routesByMethod);

        self::renderRoutesToTerminal($sortedRoutes, $command);
    }

    /**
     * Agrupa las rutas por método HTTP
     *
     * @param  array<int, array<string, mixed>>  $routes
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function groupRoutesByMethod(array $routes): array
    {
        $grouped = [];

        foreach ($routes as $route) {
            if (! isset($route['method'])) {
                continue;
            }

            $methodData = $route['method'];
            $methods = is_array($methodData) ? $methodData : explode('|', is_string($methodData) ? $methodData : '');

            foreach ($methods as $method) {
                if (! is_string($method)) {
                    continue;
                }
                $method = mb_strtoupper(trim($method));
                if (! isset($grouped[$method])) {
                    $grouped[$method] = [];
                }
                $grouped[$method][] = $route;
            }
        }

        return $grouped;
    }

    /**
     * Ordena las rutas dentro de cada método por cantidad y tipo de middleware
     *
     * @param  array<string, array<int, array<string, mixed>>>  $routesByMethod
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function sortRoutesByMiddleware(array $routesByMethod): array
    {
        $methodOrder = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
        $sortedMethods = [];

        foreach ($methodOrder as $method) {
            if (isset($routesByMethod[$method])) {
                $sortedMethods[$method] = self::sortMethodRoutes($routesByMethod[$method]);
            }
        }

        // Agregar métodos restantes que no estén en el orden predefinido
        foreach ($routesByMethod as $method => $routes) {
            if (! in_array($method, $methodOrder)) {
                $sortedMethods[$method] = self::sortMethodRoutes($routes);
            }
        }

        return $sortedMethods;
    }

    /**
     * Ordena las rutas de un método específico por middlewares
     *
     * @param  array<int, array<string, mixed>>  $routes
     * @return array<int, array<string, mixed>>
     */
    private static function sortMethodRoutes(array $routes): array
    {
        usort($routes, function ($a, $b) {
            $middlewareA = self::getMiddlewareArray($a);
            $middlewareB = self::getMiddlewareArray($b);

            $countA = count($middlewareA);
            $countB = count($middlewareB);

            if ($countA !== $countB) {
                return $countA - $countB;
            }

            $priorityA = self::getMiddlewarePriority($middlewareA);
            $priorityB = self::getMiddlewarePriority($middlewareB);

            return $priorityA - $priorityB;
        });

        return $routes;
    }

    /**
     * Extrae los middlewares de una ruta
     *
     * @param  array<string, mixed>  $route
     * @return array<int, string>
     */
    private static function getMiddlewareArray(array $route): array
    {
        $middleware = $route['middleware'] ?? [];

        if (is_string($middleware)) {
            return explode(',', $middleware);
        }

        return is_array($middleware) ? array_values(array_filter($middleware, 'is_string')) : [];
    }

    /**
     * Calcula la prioridad de un conjunto de middlewares
     *
     * @param  array<int, string>  $middlewares
     */
    private static function getMiddlewarePriority(array $middlewares): int
    {
        $priorities = [
            'auth' => 1,
            'verified' => 2,
            'role' => 3,
            'permission' => 4,
            'throttle' => 5,
        ];

        $priority = 0;
        foreach ($middlewares as $middleware) {
            $middleware = trim($middleware);
            foreach ($priorities as $key => $value) {
                if (str_contains($middleware, $key)) {
                    $priority += $value;
                    break;
                }
            }
        }

        return $priority;
    }

    /**
     * Renderiza las rutas organizadas en terminal
     *
     * @param  array<string, array<int, array<string, mixed>>>  $sortedRoutes
     */
    private static function renderRoutesToTerminal(array $sortedRoutes, ?Command $command = null): void
    {
        foreach ($sortedRoutes as $method => $routes) {
            // Título del método HTTP con colores
            $safeMethod = self::sanitizeForOutput($method);
            if ($command) {
                $command->line('');
                $command->line('<fg=cyan;options=bold>═══ Method: '.$safeMethod.' ═══</>');
            } else {
                echo "\n═══ Method: $safeMethod ═══\n";
            }

            foreach ($routes as $route) {
                $middlewares = self::getMiddlewareArray($route);
                $middlewareCount = count($middlewares);

                // Sanitizar todos los datos de salida
                $safeUri = self::sanitizeForOutput(is_string($route['uri'] ?? '') ? $route['uri'] ?? '' : '');
                $nameData = $route['name'] ?? 'No name';
                $safeName = self::sanitizeForOutput(is_string($nameData) ? $nameData : 'No name');
                $safeAction = self::sanitizeForOutput(is_string($route['action'] ?? '') ? $route['action'] ?? '' : '');
                $safeMiddlewareText = $middlewareCount > 0
                    ? self::sanitizeForOutput(implode(', ', $middlewares))
                    : 'No middleware';

                // Mostrar la ruta
                if ($command) {
                    // Si no tiene middleware, mostrar en rojo
                    if ($middlewareCount === 0) {
                        $command->line('<fg=red>┌─ URI: '.$safeUri.'</>');
                        $command->line('<fg=red>├─ Name: '.$safeName.'</>');
                        $command->line('<fg=red>├─ Action: '.$safeAction.'</>');
                        $command->line('<fg=red>└─ Middleware ('.$middlewareCount.'): '.$safeMiddlewareText.'</>');
                    } else {
                        $command->line('<fg=green>┌─ URI: '.$safeUri.'</>');
                        $command->line('<fg=white>├─ Name: '.$safeName.'</>');
                        $command->line('<fg=white>├─ Action: '.$safeAction.'</>');
                        $command->line('<fg=yellow>└─ Middleware ('.$middlewareCount.'): '.$safeMiddlewareText.'</>');
                    }
                    $command->line('');
                } else {
                    // Fallback sin colores para testing
                    echo "┌─ URI: $safeUri\n";
                    echo "├─ Name: $safeName\n";
                    echo "├─ Action: $safeAction\n";
                    echo "└─ Middleware ($middlewareCount): $safeMiddlewareText\n\n";
                }
            }
        }
    }

    /**
     * Sanitiza datos para output seguro en terminal
     */
    private static function sanitizeForOutput(string $data): string
    {
        // Remover caracteres de control y escape peligrosos
        $cleanData = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
        $data = $cleanData ?? '';

        // Escapar caracteres especiales para prevenir inyección en terminal
        $data = str_replace(['<', '>', '"', "'", '&'], ['&lt;', '&gt;', '&quot;', '&#39;', '&amp;'], $data);

        // Limitar longitud para prevenir output masivo
        if (mb_strlen($data) > 200) {
            $data = mb_substr($data, 0, 197).'...';
        }

        return trim($data);
    }
}
