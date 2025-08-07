# Driver Eloquent Laravel para ClickHouse

Package proudly made in Brazil <3!
[English](https://github.com/joaojkuligowski/laravel-fluent-clickhouse-driver/blob/main/README.md) | [Portuguese](https://github.com/joaojkuligowski/laravel-fluent-clickhouse-driver/blob/main/README-pt-BR.md)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joaojkuligowski/laravel-fluent-clickhouse-driver.svg?style=flat-square)](https://packagist.org/packages/joaojkuligowski/laravel-fluent-clickhouse-driver)
[![Total Downloads](https://img.shields.io/packagist/dt/joaojkuligowski/laravel-fluent-clickhouse-driver.svg?style=flat-square)](https://packagist.org/packages/joaojkuligowski/laravel-fluent-clickhouse-driver)


Um driver Eloquent fluente para integração do banco de dados ClickHouse com Laravel. Este pacote permite usar o ORM Eloquent do Laravel com bancos de dados ClickHouse, fornecendo uma interface familiar e poderosa para operações de análise e big data.

## Funcionalidades

- 🚀 Compatibilidade completa com Laravel Eloquent ORM
- 🔧 Suporte ao query builder fluente
- 📊 Otimizado para operações de análise do ClickHouse
- 🌐 Modos de conexão HTTP e TCP
- 🔒 Suporte a autenticação segura
- ⚡ Suporte a consultas assíncronas
- 🧪 Suíte de testes abrangente

## Requisitos

- PHP ^8.2
- Laravel ^12.0
- Servidor ClickHouse

## Instalação

Você pode instalar o pacote via Composer:

```bash
composer require joaojkuligowski/laravel-fluent-clickhouse-driver
```

O pacote registrará automaticamente seus provedores de serviço através do recurso de auto-descoberta do Laravel.

## Configuração

### Configuração do Banco de Dados

Adicione uma conexão ClickHouse ao seu arquivo `config/database.php`:

```php
'connections' => [
    // ... suas outras conexões de banco de dados
    
    'clickhouse' => [
        'driver' => 'clickhouse',
        'host' => env('CLICKHOUSE_HOST', '127.0.0.1'),
        'port' => env('CLICKHOUSE_PORT', 8123),
        'database' => env('CLICKHOUSE_DATABASE', 'default'),
        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', ''),
        'https' => env('CLICKHOUSE_HTTPS', false),
        'mode' => env('CLICKHOUSE_MODE', 'tcp'), // 'tcp' ou 'http'
        'readonly' => env('CLICKHOUSE_READONLY', false),
        'async' => env('CLICKHOUSE_ASYNC', false),
    ],
],
```

### Variáveis de Ambiente

Adicione essas variáveis ao seu arquivo `.env`:

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

## Uso

### Criando Models

Crie models ClickHouse estendendo o `LaravelClickhouseModel`:

```php
<?php

namespace App\Models;

use JoaoJ\LaravelClickhouse\LaravelClickhouseModel;

class EventoAnalytics extends LaravelClickhouseModel
{
    protected $connection = 'clickhouse';
    protected $table = 'eventos_analytics';
    
    protected $fillable = [
        'nome_evento',
        'user_id',
        'timestamp',
        'propriedades'
    ];
    
    // ClickHouse normalmente não usa IDs auto-incrementais
    public $incrementing = false;
    public $timestamps = false;
}
```

### Consultas Básicas

```php
use App\Models\EventoAnalytics;

// Inserir dados
EventoAnalytics::create([
    'nome_evento' => 'visualizacao_pagina',
    'user_id' => 123,
    'timestamp' => now(),
    'propriedades' => json_encode(['pagina' => '/home'])
]);

// Consultar dados
$eventos = EventoAnalytics::where('nome_evento', 'visualizacao_pagina')
    ->where('timestamp', '>=', now()->subDays(7))
    ->get();

// Consultas de agregação
$visualizacoes = EventoAnalytics::where('nome_evento', 'visualizacao_pagina')
    ->count();

$usuariosUnicos = EventoAnalytics::distinct('user_id')->count();
```

### Consultas de Análise Avançadas

```php
// Agregações baseadas em tempo
$estatisticasDiarias = EventoAnalytics::selectRaw('
    toDate(timestamp) as data,
    count() as eventos,
    uniq(user_id) as usuarios_unicos
')
->where('timestamp', '>=', now()->subDays(30))
->groupBy('data')
->orderBy('data')
->get();

// Usando funções específicas do ClickHouse
$paginasPopulares = EventoAnalytics::selectRaw("
    JSONExtractString(propriedades, 'pagina') as pagina,
    count() as visualizacoes
")
->where('nome_evento', 'visualizacao_pagina')
->groupBy('pagina')
->orderBy('visualizacoes', 'desc')
->limit(10)
->get();
```

### Consultas Raw

Para consultas complexas específicas do ClickHouse:

```php
use Illuminate\Support\Facades\DB;

$resultados = DB::connection('clickhouse')
    ->select('
        SELECT 
            nome_evento,
            count() as total,
            uniq(user_id) as usuarios_unicos,
            avg(toFloat64(JSONExtractString(propriedades, "duracao"))) as duracao_media
        FROM eventos_analytics 
        WHERE timestamp >= yesterday()
        GROUP BY nome_evento
        ORDER BY total DESC
    ');
```

## Modos de Conexão

### Modo TCP (Recomendado)
Melhor performance para operações de alto throughput:

```php
'mode' => 'tcp',
'port' => 9000, // Porta TCP padrão
```

### Modo HTTP
Melhor para depuração e desenvolvimento:

```php
'mode' => 'http',
'port' => 8123, // Porta HTTP padrão
```

## Melhores Práticas

### 1. Design do Schema
ClickHouse é otimizado para cargas de trabalho analíticas:

```sql
CREATE TABLE eventos_analytics (
    nome_evento String,
    user_id UInt64,
    timestamp DateTime,
    propriedades String
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(timestamp)
ORDER BY (nome_evento, timestamp);
```

### 2. Inserções em Lote
Use inserções em lote para melhor performance:

```php
$eventos = collect(range(1, 1000))->map(function ($i) {
    return [
        'nome_evento' => 'evento_teste',
        'user_id' => $i,
        'timestamp' => now(),
        'propriedades' => json_encode(['teste' => true])
    ];
});

EventoAnalytics::insert($eventos->toArray());
```

### 3. Evite Updates/Deletes
ClickHouse é otimizado para inserções e seleções. Minimize updates e deletes.

## Testando

```bash
composer test
```

## Estilo de Código

```bash
composer format
```

## Análise Estática

```bash
composer analyse
```

## Créditos

- [joaojkuligowski](https://github.com/joaojkuligowski)

## Licença

A Licença MIT (MIT). Por favor, veja [Arquivo de Licença](LICENSE.md) para mais informações.