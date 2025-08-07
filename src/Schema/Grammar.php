<?php

namespace JoaoJ\LaravelClickhouse\Schema;

use Dom\Sqlite;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;

class Grammar extends PostgresGrammar
{
    protected $transactions = false;

    public function compileTableExists($schema, $table)
    {
        $query = <<<'SQL'
        SELECT COALESCE(
            (SELECT true as exists FROM system.tables WHERE name = '%s' AND database = '%s' LIMIT 1),
            false
        ) as exists;
        SQL;

        $database = $this->connection->getConfig()['database'];

        return sprintf($query, $table, $database);
    }

    public function compileTables($schema, $withSize = false)
    {
        $database = $this->connection->getConfig()['database'];
        $tables = <<<'SQL'
        SELECT name, database as schema, total_bytes as size, comment as comments FROM system.tables
        WHERE database = '%s';
        SQL;

        return sprintf($tables, $database);
    }

    public function compileCreate(Blueprint $blueprint, Fluent $command)
    {
        $firstColumn = $blueprint->getColumns()[0]->name;

        if ($firstColumn != 'id') {
            $create = sprintf(
                'create table if not exists %s (id UInt64 not null, timestamp DateTime, %s) ENGINE = MergeTree() PRIMARY KEY (id, timestamp) ORDER BY (%s, timestamp) SETTINGS index_granularity = 8192',
                $this->wrapTable($blueprint),
                implode(', ', $this->getColumns($blueprint)),
                'id'
            );

            return $create;
        }
        $create = sprintf(
            'create table if not exists %s (%s) ENGINE = MergeTree() ORDER BY (%s) SETTINGS index_granularity = 8192',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint)),
            $firstColumn
        );

        dump($create);

        return $create;
    }

    public function compilePrimary(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->columnize($command->columns);
        if ($columns != 'id') {
            $columns = 'id';
        }

        $query = 'SELECT 1=1';
    }

    public function compileDropTable(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table ' . $this->wrapTable($blueprint);
    }

    public function compileDropAllTables($tables)
    {
        $query = '';
        foreach ($tables as $table) {
            $query .= 'drop table if exists ' . $this->wrapTable($table) . ';';
        }

        return $query;
    }

    protected function typeTimestamp(Fluent $column)
    {
        return 'DateTime64(3)';
    }

    protected function typeEnum(Fluent $column)
    {
        $compiledEnum = [];
        foreach ($column->allowed as $key => $item) {
            $key = $key + 1;
            $compiledEnum[] = "'$item' = $key";
        }
        return 'Enum(' . implode(', ', $compiledEnum) . ')';
    }

    protected function typeBigIntegerUnsigned(Fluent $column)
    {
        return 'UInt64';
    }

    protected function typeBigInteger(Fluent $column)
    {
        return 'UInt64';
    }

    protected function typeString(Fluent $column)
    {
        return 'String';
    }

    protected function typeInteger(Fluent $column)
    {
        return 'UInt64';
    }

    protected function typeSmallInteger(Fluent $column)
    {
        return 'smallint';
    }

    public function compileIndex(Blueprint $blueprint, Fluent $command)
    {
        return sprintf(
            'create index %s on %s%s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $command->algorithm ? ' using ' . $command->algorithm : '',
            $this->columnize($command->columns)
        );
    }

    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        $nullable = $command->column->nullable;
        $type = $this->getType($command->column);
        if ($nullable) {
            $query = sprintf(
                'alter table %s add column "%s" Nullable(%s)',
                $this->wrapTable($blueprint),
                $command->column->name,
                $type
            );

            return $query;
        }

        $query = sprintf(
            'alter table %s add column %s',
            $this->wrapTable($blueprint),
            $this->getColumn($blueprint, $command->column)
        );

        if (strpos($query, 'null')) {
            $query = str_replace('null', '', $query);
        }

        return $query;
    }
}
