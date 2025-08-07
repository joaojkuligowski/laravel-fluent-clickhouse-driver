<?php

namespace JoaoJ\LaravelClickhouse;

use JoaoJ\LaravelClickhouse\Query\Builder;
use JoaoJ\LaravelClickhouse\Query\Grammar as QueryGrammar;
use JoaoJ\LaravelClickhouse\Query\Processor;
use JoaoJ\LaravelClickhouse\Schema\Grammar as SchemaGrammar;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Str;
use JoaoJ\LaravelClickhouse\Connectors\{
    ClickhouseHttp,
    ClickhouseTcp
};

class LaravelClickhouseConnection extends PostgresConnection
{
    private $installed_extensions = [];
    public function __construct($config)
    {
        $this->config = $config;
        $this->postProcessor = new Processor();

        $this->useDefaultPostProcessor();
        $this->useDefaultSchemaGrammar();
        $this->useDefaultQueryGrammar();
    }

    public function query()
    {
        return $this->getDefaultQueryBuilder();
    }

    public function table($table, $as = null)
    {
        return $this->query()->from($table, $as);
    }

    private function quote($str)
    {
        if (extension_loaded('sqlite3')) {
            return "'" . \SQLite3::escapeString($str) . "'";
        }
        if (extension_loaded('pdo_sqlite')) {
            return (new \PDO('sqlite::memory:'))->quote($str);
        }

        return "'" . preg_replace("/'/m", "''", $str) . "'";
    }

    private function getClickhouseQuery($query, $bindings = [], $safeMode = false)
    {
        $escapeQuery = $query;

        $countBindings = count($bindings ?? []);
        if ($countBindings > 0) {
            foreach ($bindings as $index => $val) {
                $escapeQuery = Str::replaceFirst('?', $this->quote($val), $escapeQuery);
            }
        }

        return $escapeQuery;
    }

    private function executeClickhouseSql($sql, $bindings = [], $safeMode = false)
    {
        $query = $this->getClickhouseQuery($sql, $bindings, $safeMode);


        if ($this->config['mode'] === 'http') {
            $client = ClickhouseHttp::getInstance($this->config)->getClient();
            $client->database($this->config['database']);
            $client->ping(true);
            // $client->settings()->set('allow_create_index_without_type', true);
            // $client->settings()->set('max_execution_time', $this->config['timeout'] ?? 0);
            // $client->settings()->set('max_block_size', false);
            
            if ($this->config['async']) {
                $raw_output = $client->selectAsync($query);
                $client->executeAsync();
            } else {
                $raw_output = $client->write($query);
            }

            return $raw_output->rows();
        } else {
            $client = ClickHouseTcp::getInstance($this->config)->getClient();
            return $client->query($query);
        }
    }

    private function runQueryWithLog($query, $bindings = [])
    {
        $start = microtime(true);
        if (empty($query)) {
            return [];
        }
        $result = $this->executeClickhouseSql($query, $bindings) ?? [];

        $this->logQuery(
            $query,
            [],
            $this->getElapsedTime($start)
        );

        return $result;
    }

    public function statement($query, $bindings = [])
    {
        $this->runQueryWithLog($query, $bindings);

        return true;
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->runQueryWithLog($query, $bindings);
    }

    public function affectingStatement($query, $bindings = [])
    {
        //for update/delete
        //todo: we have to use : returning * to get list of affected rows; currently causing error;
        return $this->runQueryWithLog($query, $bindings);
    }

    private function getDefaultQueryBuilder()
    {
        return new Builder($this, $this->getDefaultQueryGrammar(), $this->getDefaultPostProcessor());
    }

    public function getDefaultQueryGrammar()
    {
        return new QueryGrammar($this);
    }

    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    public function getDefaultPostProcessor()
    {
        return new Processor();
    }

    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new \JoaoJ\LaravelClickhouse\Schema\Builder($this);
    }

    public function useDefaultSchemaGrammar()
    {
        $this->schemaGrammar = $this->getDefaultSchemaGrammar();
    }

    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar($this);
    }

    /**
     * Get the schema grammar used by the connection.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    public function getSchemaGrammar()
    {
        return $this->schemaGrammar;
    }
}
