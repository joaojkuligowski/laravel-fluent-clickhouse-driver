<?php

namespace JoaoJ\LaravelClickhouse\Connectors;

class ClickhouseHttp
{
  /**
   * A instância única da classe
   *
   * @var ClickhouseHttp|null
   */
  private static ?ClickhouseHttp $instance = null;

  /**
   * Clickhouse Http Client
   *
   * @var \ClickHouseDB\Client
   */
  public \ClickHouseDB\Client $client;

  /**
   * Construtor privado para prevenir criação direta de objetos
   *
   * @param array $config Configuração do cliente ClickHouse
   */
  private function __construct(array $config)
  {
    $this->client = new \ClickHouseDB\Client($config);
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
   * @return ClickhouseHttp
   * @throws \Exception
   */
  public static function getInstance(?array $config = null): ClickhouseHttp
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
   *
   * @return \ClickHouseDB\Client
   */
  public function getClient(): \ClickHouseDB\Client
  {
    return $this->client;
  }
}
