<?php

declare(strict_types=1);

namespace FrogUri\Collections;

use Illuminate\Support\Collection;

final class RouteCollection
{
    /**
     * @var Collection<int, array<string, mixed>>
     */
    private Collection $routes;

    /**
     * @param  array<int, array<string, mixed>>  $routeData
     */
    public function __construct(array $routeData)
    {
        $this->routes = collect($routeData);
    }

    /**
     * @param  array<int, array<string, mixed>>  $jsonData
     */
    public static function fromJson(array $jsonData): self
    {
        return new self($jsonData);
    }

    /**
     * @param  string|array<int, string>  $middleware
     */
    public function filterByMiddleware(string|array $middleware): self
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];

        $filtered = $this->routes->filter(function ($route) use ($middlewares) {
            $routeMiddlewares = $route['middleware'] ?? [];
            if (! is_array($routeMiddlewares)) {
                return false;
            }

            foreach ($middlewares as $mw) {
                if (in_array($mw, $routeMiddlewares)) {
                    return true;
                }
            }

            return false;
        });

        /** @var array<int, array<string, mixed>> $result */
        $result = array_values($filtered->toArray());

        return new self($result);
    }

    /**
     * @param  string|array<int, string>  $method
     */
    public function filterByMethod(string|array $method): self
    {
        $methods = is_array($method) ? $method : [$method];
        $methods = array_map(function (string $m): string {
            return mb_strtoupper($m);
        }, $methods);

        $filtered = $this->routes->filter(function ($route) use ($methods) {
            $methodData = $route['method'] ?? '';
            if (! is_string($methodData)) {
                return false;
            }
            $routeMethods = explode('|', mb_strtoupper($methodData));

            foreach ($methods as $m) {
                if (in_array($m, $routeMethods)) {
                    return true;
                }
            }

            return false;
        });

        /** @var array<int, array<string, mixed>> $result */
        $result = array_values($filtered->toArray());

        return new self($result);
    }

    public function filterByUri(string $pattern): self
    {
        $filtered = $this->routes->filter(function ($route) use ($pattern) {
            $uri = $route['uri'] ?? '';

            return fnmatch($pattern, is_string($uri) ? $uri : '');
        });

        /** @var array<int, array<string, mixed>> $result */
        $result = array_values($filtered->toArray());

        return new self($result);
    }

    public function filterByName(string $pattern): self
    {
        $filtered = $this->routes->filter(function ($route) use ($pattern) {
            $name = $route['name'] ?? '';

            return fnmatch($pattern, is_string($name) ? $name : '');
        });

        /** @var array<int, array<string, mixed>> $result */
        $result = array_values($filtered->toArray());

        return new self($result);
    }

    /**
     * @return array<int, string>
     */
    public function getMiddlewaresForRoute(string $routeName): array
    {
        $route = $this->routes->firstWhere('name', $routeName);

        $middlewares = $route['middleware'] ?? [];

        return is_array($middlewares) ? array_values(array_filter($middlewares, 'is_string')) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRoutesWithMiddleware(string $middleware): array
    {
        return $this->filterByMiddleware($middleware)->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRoutesByMethod(string $method): array
    {
        return $this->filterByMethod($method)->toArray();
    }

    /**
     * @return array<int, string>
     */
    public function getAllMiddlewares(): array
    {
        $middlewares = $this->routes
            ->pluck('middleware')
            ->flatten()
            ->filter(fn ($item) => is_string($item))
            ->unique()
            ->sort()
            ->values();

        /** @var array<int, string> $result */
        $result = array_values($middlewares->toArray());

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function getAllMethods(): array
    {
        $methods = $this->routes
            ->pluck('method')
            ->map(fn ($method) => explode('|', is_string($method) ? $method : ''))
            ->flatten()
            ->filter(fn ($item) => is_string($item))
            ->unique()
            ->sort()
            ->values();

        /** @var array<int, string> $result */
        $result = array_values($methods->toArray());

        return $result;
    }

    public function count(): int
    {
        return $this->routes->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = array_values($this->routes->toArray());

        return $result;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function toCollection(): Collection
    {
        return $this->routes;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupByMiddleware(): array
    {
        $grouped = [];

        foreach ($this->routes as $route) {
            $middlewares = $route['middleware'] ?? [];
            if (! is_array($middlewares)) {
                continue;
            }

            foreach ($middlewares as $middleware) {
                if (! is_string($middleware)) {
                    continue;
                }
                if (! isset($grouped[$middleware])) {
                    $grouped[$middleware] = [];
                }
                $grouped[$middleware][] = $route;
            }
        }

        return $grouped;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupByMethod(): array
    {
        $grouped = [];

        foreach ($this->routes as $route) {
            $methodData = $route['method'] ?? '';
            if (! is_string($methodData)) {
                continue;
            }
            $methods = explode('|', $methodData);

            foreach ($methods as $method) {
                if (! isset($grouped[$method])) {
                    $grouped[$method] = [];
                }
                $grouped[$method][] = $route;
            }
        }

        return $grouped;
    }

    public function getRoutesWithoutMiddleware(): self
    {
        $filtered = $this->routes->filter(function ($route) {
            $middlewares = $route['middleware'] ?? [];

            return empty($middlewares);
        });

        /** @var array<int, array<string, mixed>> $result */
        $result = array_values($filtered->values()->toArray());

        return new self($result);
    }

    /**
     * Filter routes that do NOT contain any of the specified middlewares
     *
     * @param  string|array<int, string>  $middleware
     */
    public function excludeByMiddleware(string|array $middleware): self
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];

        $filtered = $this->routes->filter(function ($route) use ($middlewares) {
            $routeMiddlewares = $route['middleware'] ?? [];
            if (! is_array($routeMiddlewares)) {
                return true; // Route has no middlewares, so it's not excluded
            }

            // Return true only if NONE of the exclude middlewares are found
            foreach ($middlewares as $mw) {
                if (in_array($mw, $routeMiddlewares)) {
                    return false; // Found excluded middleware, filter out this route
                }
            }

            return true; // Route doesn't have any of the excluded middlewares
        });

        /** @var array<int, array<string, mixed>> $result */
        $result = array_values($filtered->toArray());

        return new self($result);
    }

    /**
     * Get routes that don't have any of the specified middlewares
     *
     * @param  string|array<int, string>  $middleware
     * @return array<int, array<string, mixed>>
     */
    public function getRoutesWithoutSpecificMiddleware(string|array $middleware): array
    {
        return $this->excludeByMiddleware($middleware)->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDangerousRoutes(): array
    {
        return $this->getRoutesWithoutMiddleware()->toArray();
    }
}
