<?php

namespace JoaoJ\LaravelClickhouse\Query;

use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Query\Builder;

class Processor extends PostgresProcessor
{
  public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
  {
    $connection = $query->getConnection();
    $generatedInt64 = rand(strtotime(date('Y-m-d H:i:s')), time());

    if ($sequence) {
      $sql = str_replace('" ("', '" ("' . $sequence . '", "', $sql);
      $sql = str_replace('values (', 'values (' . $generatedInt64 . ', ', $sql);
    }

    $result = $connection->select($sql, $values);

    return $generatedInt64;
  }
}
