<?php

namespace JoaoJ\LaravelClickhouse\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\{PostgresGrammar, SQLiteGrammar};
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;

class Grammar extends PostgresGrammar
{
    protected string $tablePrefix = '';

    protected function compileFrom(Builder $query, $table)
    {
        if ($this->isExpression($table)) {
            return parent::compileFrom($query, $table);
        }
        if (stripos($table, ' as ') !== false) {
            $segments = preg_split('/\s+as\s+/i', $table);
            return "from " . $this->wrapFromClause($segments[0], true)
                . " as "
                . $this->wrapFromClause($segments[1]);
        }

        return "from " . $this->wrapFromClause($table, true);
    }

    private function wrapFromClause($value, $prefixAlias = false)
    {
        if (!Str::endsWith($value, ')')) { //is function
            return $this->quoteString(($prefixAlias ? $this->tablePrefix : '') . $value);
        }
        return ($prefixAlias ? $this->tablePrefix : '') . $value;
    }

    public function compileTruncate(Builder $query)
    {
        return ['truncate ' . $this->wrapTable($query->from) => []];
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     *
     * @throws \Exception
     */

    public function compileUpdate(Builder $query, array $values)
    {
        if (isset($query->joins) || isset($query->limit)) {
            return throw new \Exception('update with joins or limit not supported in ClickHouse');
        }

        $sql = 'alter table ' . $this->wrapTable($query->from) . ' update ';
        foreach ($values as $key => $value) {
            $sql .= $this->wrap($key) . ' = ' . $this->parameter($value);
        }

        $sql .= ' ' . $this->compileWheres($query, $query->wheres);
        return $sql;
    }


    public function compileInsert(Builder $query, array $values)
    {
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);

        if (empty($values)) {
            return "insert into {$table} default values";
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }


        $columns = $this->columnize(array_keys(reset($values)));

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same number of parameter
        // bindings so we will loop through the record and parameterize them all.
        $parameters = (new Collection($values))->map(function ($record) {
            return '(' . $this->parameterize($record) . ')';
        })->implode(', ');


        $insert = "insert into $table ($columns) values $parameters";

        return $insert;
    }

    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        // get next sequence by builder
        return $this->compileInsert($query, $values);
    }
}
