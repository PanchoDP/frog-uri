<?php

declare(strict_types=1);

namespace FrogUri\Actions;

use Illuminate\Support\Facades\Process;

final class GetJsonAction
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function handle(): array
    {
        $result = Process::run('php artisan route:list -vv --json');

        if ($result->successful()) {
            $output = mb_trim($result->output());

            // Validar que no esté vacío
            if (empty($output)) {
                return [];
            }

            $json_data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Frog-URI: JSON decode error - '.json_last_error_msg());

                return [];
            }

            if (! is_array($json_data)) {
                error_log('Frog-URI: Invalid JSON structure - expected array');

                return [];
            }

            return self::sanitizeRouteData(array_values($json_data));
        }

        return [];
    }

    /**
     * Sanitiza y valida los datos de las rutas
     *
     * @param  array<int, mixed>  $routes
     * @return array<int, array<string, mixed>>
     */
    private static function sanitizeRouteData(array $routes): array
    {
        $sanitized = [];

        foreach ($routes as $route) {
            // Validar que sea un array asociativo
            if (! is_array($route)) {
                continue;
            }

            // Sanitizar campos individuales
            $cleanRoute = [
                'method' => self::sanitizeString(is_string($route['method'] ?? '') ? $route['method'] ?? '' : ''),
                'uri' => self::sanitizeString(is_string($route['uri'] ?? '') ? $route['uri'] ?? '' : ''),
                'name' => isset($route['name']) && is_string($route['name']) ? self::sanitizeString($route['name']) : null,
                'action' => self::sanitizeString(is_string($route['action'] ?? '') ? $route['action'] ?? '' : ''),
                'middleware' => self::sanitizeMiddleware($route['middleware'] ?? []),
            ];

            if (! empty($cleanRoute['method']) && ! empty($cleanRoute['uri'])) {
                $sanitized[] = $cleanRoute;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitiza strings para prevenir inyección
     */
    private static function sanitizeString(string $value): string
    {
        $cleanValue = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        if ($cleanValue === null) {
            $cleanValue = '';
        }

        if (mb_strlen($cleanValue) > 500) {
            $cleanValue = mb_substr($cleanValue, 0, 500);
        }

        return mb_trim($cleanValue) ?: '';
    }

    /**
     * Sanitiza array de middlewares
     *
     * @return array<int, string>
     */
    private static function sanitizeMiddleware(mixed $middleware): array
    {
        if (is_string($middleware)) {
            $middleware = explode(',', $middleware);
        }

        if (! is_array($middleware)) {
            return [];
        }

        $sanitized = [];
        foreach ($middleware as $item) {
            if (is_string($item)) {
                $clean = self::sanitizeString($item);
                if (! empty($clean)) {
                    $sanitized[] = $clean;
                }
            }
        }

        return $sanitized;
    }
}
