# Laravel ClickHouse Eloquent Driver

Package proudly made in Brazil <3!
[English](https://github.com/joaojkuligowski/laravel-fluent-clickhouse-driver/README.md) | [Portuguese](https://github.com/joaojkuligowski/laravel-fluent-clickhouse-driver/README-pt-BR.md)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joaojkuligowski/laravel-fluent-clickhouse-driver.svg?style=flat-square)](https://packagist.org/packages/joaojkuligowski/laravel-fluent-clickhouse-driver)
[![Total Downloads](https://img.shields.io/packagist/dt/joaojkuligowski/laravel-fluent-clickhouse-driver.svg?style=flat-square)](https://packagist.org/packages/joaojkuligowski/laravel-fluent-clickhouse-driver)

A fluent Eloquent driver for ClickHouse database integration with Laravel. This package allows you to use Laravel's Eloquent ORM with ClickHouse databases, providing a familiar and powerful interface for analytics and big data operations.

## Features

- 🚀 Full Laravel Eloquent ORM compatibility
- 🔧 Fluent query builder support
- 📊 Optimized for ClickHouse analytics operations
- 🌐 HTTP and TCP connection modes
- 🔒 Secure authentication support
- ⚡ Asynchronous query support
- 🧪 Comprehensive testing suite

## Requirements

- PHP ^8.2
- Laravel ^12.0
- ClickHouse Server

## Installation

You can install the package via Composer:

```bash
composer require joaojkuligowski/laravel-fluent-clickhouse-driver
```

The package will automatically register its service providers through Laravel's auto-discovery feature.

## Configuration

### Database Configuration

Add a ClickHouse connection to your `config/database.php` file:

```php
'connections' => [
    // ... your other database connections
    
    'clickhouse' => [
        'driver' => 'clickhouse',
        'host' => env('CLICKHOUSE_HOST', '127.0.0.1'),
        'port' => env('CLICKHOUSE_PORT', 8123),
        'database' => env('CLICKHOUSE_DATABASE', 'default'),
        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', ''),
        'https' => env('CLICKHOUSE_HTTPS', false),
        'mode' => env('CLICKHOUSE_MODE', 'tcp'), // 'tcp' or 'http'
        'readonly' => env('CLICKHOUSE_READONLY', false),
        'async' => env('CLICKHOUSE_ASYNC', false),
    ],
],
```

### Environment Variables

Add these variables to your `.env` file:

```env
CLICKHOUSE_HOST=127.0.0.1
CLICKHOUSE_PORT=8123
CLICKHOUSE_DATABASE=default
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=
CLICKHOUSE_HTTPS=false
CLICKHOUSE_MODE=tcp
CLICKHOUSE_READONLY=false
CLICKHOUSE_ASYNC=false
```

## Usage

### Creating Models

Create ClickHouse models by extending the `LaravelClickhouseModel`:

```php
<?php

namespace App\Models;

use JoaoJ\LaravelClickhouse\LaravelClickhouseModel;

class AnalyticsEvent extends LaravelClickhouseModel
{
    protected $connection = 'clickhouse';
    protected $table = 'analytics_events';
    
    protected $fillable = [
        'event_name',
        'user_id',
        'timestamp',
        'properties'
    ];
    
    // ClickHouse doesn't use auto-incrementing IDs typically
    public $incrementing = false;
    public $timestamps = false;
}
```

### Basic Queries

```php
use App\Models\AnalyticsEvent;

// Insert data
AnalyticsEvent::create([
    'event_name' => 'page_view',
    'user_id' => 123,
    'timestamp' => now(),
    'properties' => json_encode(['page' => '/home'])
]);

// Query data
$events = AnalyticsEvent::where('event_name', 'page_view')
    ->where('timestamp', '>=', now()->subDays(7))
    ->get();

// Aggregate queries
$pageViews = AnalyticsEvent::where('event_name', 'page_view')
    ->count();

$uniqueUsers = AnalyticsEvent::distinct('user_id')->count();
```

### Advanced Analytics Queries

```php
// Time-based aggregations
$dailyStats = AnalyticsEvent::selectRaw('
    toDate(timestamp) as date,
    count() as events,
    uniq(user_id) as unique_users
')
->where('timestamp', '>=', now()->subDays(30))
->groupBy('date')
->orderBy('date')
->get();

// Using ClickHouse-specific functions
$topPages = AnalyticsEvent::selectRaw("
    JSONExtractString(properties, 'page') as page,
    count() as views
")
->where('event_name', 'page_view')
->groupBy('page')
->orderBy('views', 'desc')
->limit(10)
->get();
```

### Raw Queries

For complex ClickHouse-specific queries:

```php
use Illuminate\Support\Facades\DB;

$results = DB::connection('clickhouse')
    ->select('
        SELECT 
            event_name,
            count() as total,
            uniq(user_id) as unique_users,
            avg(toFloat64(JSONExtractString(properties, "duration"))) as avg_duration
        FROM analytics_events 
        WHERE timestamp >= yesterday()
        GROUP BY event_name
        ORDER BY total DESC
    ');
```

## Connection Modes

### TCP Mode (Recommended)
Better performance for high-throughput operations:

```php
'mode' => 'tcp',
'port' => 9000, // Default TCP port
```

### HTTP Mode
Better for debugging and development:

```php
'mode' => 'http',
'port' => 8123, // Default HTTP port
```

## Best Practices

### 1. Schema Design
ClickHouse is optimized for analytical workloads:

```sql
CREATE TABLE analytics_events (
    event_name String,
    user_id UInt64,
    timestamp DateTime,
    properties String
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(timestamp)
ORDER BY (event_name, timestamp);
```

### 2. Batch Inserts
Use batch inserts for better performance:

```php
$events = collect(range(1, 1000))->map(function ($i) {
    return [
        'event_name' => 'test_event',
        'user_id' => $i,
        'timestamp' => now(),
        'properties' => json_encode(['test' => true])
    ];
});

AnalyticsEvent::insert($events->toArray());
```

### 3. Avoid Updates/Deletes
ClickHouse is optimized for inserts and selects. Minimize updates and deletes.

## Testing

```bash
composer test
```

## Code Style

```bash
composer format
```

## Static Analysis

```bash
composer analyse
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.
s
## Credits

- [joaojkuligowski](https://github.com/joaojkuligowski)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.