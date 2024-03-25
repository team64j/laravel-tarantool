<?php

declare(strict_types=1);

namespace Team64j\LaravelTarantool\Database;

use Closure;
use Exception;
use Generator;
use Illuminate\Database;
use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Schema\Grammars\Grammar as SchemaGrammar;
use Tarantool\Client\Client;
use Tarantool\Client\SqlQueryResult;
use Team64j\LaravelTarantool\Database\Query\Processors\TarantoolProcessor;
use Team64j\LaravelTarantool\Database\Schema\Grammars\TarantoolGrammar;
use Team64j\LaravelTarantool\Traits\DsnTrait;
use Team64j\LaravelTarantool\Traits\HelperTrait;
use Team64j\LaravelTarantool\Traits\QueryTrait;

class Connection extends BaseConnection
{
    use DsnTrait, QueryTrait, HelperTrait;

    /**
     * @var Client
     */
    protected Client $connection;

    /**
     * Create a new database connection instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $dsn = $this->getDsn($config);

        $connection = $this->createConnection($dsn);

        $this->setClient($connection);

        $this->useDefaultPostProcessor();
        $this->useDefaultSchemaGrammar();
        $this->useDefaultQueryGrammar();
    }

    /**
     * Create a new Tarantool connection.
     *
     * @param string $dsn
     *
     * @return Client
     */
    protected function createConnection(string $dsn): Client
    {
        return Client::fromDsn($dsn);
    }

    /**
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     *
     * @return Generator
     * @throws Exception
     */
    public function cursor($query, $bindings = [], $useReadPdo = true): Generator
    {
        /** @var SqlQueryResult $queryResult */
        $queryResult = $this->run($query, $bindings, function () {});

        $metaData = $queryResult->getMetadata();

        array_walk_recursive($metaData, function (&$value) {
            $value = strtolower($value);
        });

        $result = new SqlQueryResult($queryResult->getData(), $metaData);

        return $result->getIterator();
    }

    /**
     * @param Client $connection
     *
     * @return self
     */
    public function setClient(Client $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * return Tarantool object.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultPostProcessor(): Database\Query\Processors\Processor|TarantoolProcessor
    {
        return new TarantoolProcessor();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultQueryGrammar(): Database\Query\Grammars\Grammar|Query\Grammars\TarantoolGrammar
    {
        return new Query\Grammars\TarantoolGrammar();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultSchemaGrammar(): SchemaGrammar|TarantoolGrammar|null
    {
        return new TarantoolGrammar();
    }
}
