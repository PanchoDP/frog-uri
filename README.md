

# FrogUri

<p align="center">
<a ><img src="https://img.shields.io/badge/PHP-8.3%2B-blue" alt="Php"></a>
<a ><img src="https://img.shields.io/packagist/dt/panchodp/frog-uri.svg?" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/panchodp/frog-uri"><img src="https://img.shields.io/packagist/v/panchodp/frog-uri.svg?" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/panchodp/frog-uri"><img src="https://img.shields.io/badge/License-MIT-green" alt="License"></a>
<a href="https://github.com/PanchoDP/frog-uri/actions/workflows/tests.yml"><img src="https://github.com/PanchoDP/frog-uri/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
</p>


> [!IMPORTANT]
> Caution: This package is a work in progress and may not be production-ready. Use at your own risk.

**Inspect, check, review and filter Laravel middlewares applied to your routes.**

FrogUri is a powerful Laravel package that helps you analyze your application's routing structure, identify security vulnerabilities, and understand middleware distribution across your routes.

## Installation

Install the package via Composer:

```bash
composer require panchodp/frog-uri --dev
```

The package will automatically register itself via Laravel's package discovery.

## Usage

### Basic Analysis

Analyze all your application routes and get detailed information about middlewares:

```bash
php artisan frog:analyze
```

This command will show you:
- Total number of routes
- Middleware distribution
- Routes summary and statistics

### Security Analysis (Danger Mode)

Identify potentially vulnerable routes without middleware protection:

```bash
php artisan frog:analyze --danger
```

This will display:
- � **Routes without any middleware** (potential security risks)
- Formatted list with HTTP methods, URIs, and controllers
- Security recommendations

### Interactive Route Filtering

Filter and analyze routes interactively with the new `frog:filter` command:

```bash
php artisan frog:filter
```

**Features:**
- 📋 **Interactive middleware selection** with numbered table interface
- 🎯 **Flexible filtering options**: Select individual middlewares (1,3,5) or all middlewares
- 🚫 **Exclude mode** for security audits: `php artisan frog:filter --exclude`
- 📊 **Visual results** with organized route information

#### Include Mode (Default)
Shows routes that **HAVE** the selected middlewares:
```bash
php artisan frog:filter
# Select middleware numbers to see routes WITH those middlewares
```

#### Exclude Mode (Security Audits)
Shows routes that **DO NOT HAVE** the selected middlewares:
```bash
php artisan frog:filter --exclude
# Select middleware numbers to find routes WITHOUT those middlewares
```

**Common Security Use Cases:**
```bash
# Find routes without authentication
php artisan frog:filter --exclude
# Select "auth" to find unprotected routes

# Find routes without rate limiting
php artisan frog:filter --exclude
# Select "throttle" to find routes without rate limiting

# Find routes without both auth and admin protection
php artisan frog:filter --exclude
# Select "auth,admin" to find vulnerable admin routes
```

## Features

### RouteCollection API

The package provides a powerful `RouteCollection` class for programmatic route analysis:

```php
use FrogUri\Actions\GetJsonAction;
use FrogUri\Actions\MappingAction;

// Get route data
$jsonData = GetJsonAction::handle();
$collection = MappingAction::handle($jsonData);

// Filter by middleware
$authRoutes = $collection->filterByMiddleware('auth');
$multipleMiddlewares = $collection->filterByMiddleware(['auth', 'verified']);

// Filter by HTTP method
$getRoutes = $collection->filterByMethod('GET');
$postRoutes = $collection->filterByMethod(['POST', 'PUT']);

// Chain filters
$secureApiRoutes = $collection
    ->filterByUri('api/*')
    ->filterByMiddleware('auth')
    ->filterByMethod('GET');

// Security analysis
$vulnerableRoutes = $collection->getRoutesWithoutMiddleware();
$dangerousRoutes = $collection->getDangerousRoutes();

// NEW: Exclude filtering for security audits
$unprotectedRoutes = $collection->excludeByMiddleware('auth');
$noRateLimitRoutes = $collection->excludeByMiddleware(['throttle', 'rate_limit']);
$adminRoutesWithoutAuth = $collection
    ->filterByUri('admin/*')
    ->excludeByMiddleware('auth');
```

### Available Filter Methods

| Method | Description | Example |
|--------|-------------|---------|
| `filterByMiddleware()` | Filter routes by middleware | `$collection->filterByMiddleware('auth')` |
| `excludeByMiddleware()` | **NEW** Exclude routes with middleware | `$collection->excludeByMiddleware('auth')` |
| `filterByMethod()` | Filter routes by HTTP method | `$collection->filterByMethod('POST')` |
| `filterByUri()` | Filter routes by URI pattern | `$collection->filterByUri('api/*')` |
| `filterByName()` | Filter routes by route name | `$collection->filterByName('admin.*')` |
| `getRoutesWithoutMiddleware()` | Get routes without any middleware | `$collection->getRoutesWithoutMiddleware()` |
| `getRoutesWithoutSpecificMiddleware()` | **NEW** Get routes without specific middleware | `$collection->getRoutesWithoutSpecificMiddleware('auth')` |

### Utility Methods

```php
// Get all unique middlewares
$middlewares = $collection->getAllMiddlewares();

// Get all HTTP methods
$methods = $collection->getAllMethods();

// Group routes by middleware
$groupedByMiddleware = $collection->groupByMiddleware();

// Group routes by HTTP method
$groupedByMethod = $collection->groupByMethod();

// Get route count
$totalRoutes = $collection->count();
```

## Use Cases

### Security Audits
- **Interactive filtering** with `frog:filter --exclude` to find vulnerable routes
- Identify routes without authentication middleware
- Find API endpoints missing rate limiting
- Locate admin routes without proper protection
- **Bulk security scanning** using exclude mode for multiple middlewares

### Code Reviews
- Ensure consistent middleware application
- Verify route protection standards
- Document middleware usage patterns

### Development
- Debug routing issues
- Understand middleware inheritance
- Validate route configurations

## Credits

- [Francisco de Pablo](https://github.com/panchodp)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.