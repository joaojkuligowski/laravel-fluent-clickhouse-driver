<?php

namespace JoaoJ\LaravelClickhouse\Query;

use Illuminate\Support\Arr;
use JoaoJ\LaravelClickhouse\Query\Grammars\Grammar;

class Builder extends \Illuminate\Database\Query\Builder
{
  /**
   * Insert new records into the database.
   *
   * @return bool
   */
  public function insert(array $values)
  {
    // Since every insert gets treated like a batch insert, we will make sure the
    // bindings are structured in a way that is convenient when building these
    // inserts statements by verifying these elements are actually an array.
    if (empty($values)) {
      return true;
    }

    if (! is_array(reset($values))) {
      $values = [$values];
    }

    // Here, we will sort the insert keys for every record so that each insert is
    // in the same order for the record. We need to make sure this is the case
    // so there are not any errors or problems when inserting these records.
    else {
      foreach ($values as $key => $value) {
        ksort($value);

        $values[$key] = $value;
      }
    }

    $this->applyBeforeQueryCallbacks();

    // Finally, we will run this query against the database connection and return
    // the results. We will need to also flatten these bindings before running
    // the query so they are all in one huge, flattened array for execution.

    $select = count($this->connection->select(
      $this->grammar->compileSelect($this),
      $this->cleanBindings(Arr::flatten($values, 1))
    ));

    $nextId = $select + 1;
    $values = array_map(function ($value) use ($nextId) {
      return array_merge(['id' => $nextId++], $value);
    }, $values);

    $insertedData = $this->connection->insert(
      $this->grammar->compileInsert($this, $values),
      $this->cleanBindings(Arr::flatten($values, 1))
    );
  }
}
