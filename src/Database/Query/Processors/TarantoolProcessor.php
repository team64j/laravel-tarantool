<?php

declare(strict_types=1);

namespace Team64j\LaravelTarantool\Database\Query\Processors;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;
use Tarantool\Client\SqlUpdateResult;

class TarantoolProcessor extends Processor
{
    /**
     * Process an  "insert get ID" query.
     *
     * @param Builder $query
     * @param string $sql
     * @param array $values
     * @param string|null $sequence
     *
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null): int
    {
        /** @var SqlUpdateResult $result */
        $result = $query->getConnection()->insert($sql, $values);
        $id = $result->getAutoincrementIds()[0];

        return is_numeric($id) ? (int) $id : $id;
    }
}
