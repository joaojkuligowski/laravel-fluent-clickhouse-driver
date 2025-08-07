<?php

namespace JoaoJ\LaravelClickhouse\Connectors;

class ClickhouseTcp
{
  /**
   * A instância única da classe
   *
   * @var ClickhouseTcp|null
   */
  private static ?ClickhouseTcp $instance = null;

  /**
   * Clickhouse Tcp Client
   *
   * @var \OneCk\Client
   */
  public \OneCk\Client $client;

  /**
   * Construtor privado para prevenir criação direta de objetos
   *
   * @param array $config Configuração do cliente ClickHouse
   */
  private function __construct(array $config)
  {
    $this->client = new \OneCk\Client(
      'tcp://' . $config['host'] . ':' . $config['port'],
      $config['username'],
      $config['password'],
      $config['database']
    );
  }

  /**
   * Previne a clonagem do objeto
   *
   * @return void
   */
  private function __clone() {}

  /**
   * Previne a deserialização do objeto
   *
   * @return void
   * @throws \Exception
   */
  public function __wakeup()
  {
    throw new \Exception("Não é possível dessserializar um singleton.");
  }

  /**
   * Obtém a instância única da classe
   *
   * @param array|null $config Configuração do cliente ClickHouse (apenas necessário na primeira chamada)
   * @return ClickHouseTcp
   * @throws \Exception
   */
  public static function getInstance(?array $config = null): ClickHouseTcp
  {
    return new self($config);
  }

  /**
   * Reseta a instância do singleton (útil para testes)
   *
   * @return void
   */
  public static function resetInstance(): void
  {
    self::$instance = null;
  }

  /**
   * Retorna o cliente ClickHouse
   * @return \OneCk\Client
   */
  public function getClient()
  {
    return $this->client;
  }
}
