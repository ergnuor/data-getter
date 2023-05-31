<?php
declare(strict_types=1);

namespace Ergnuor\DataGetter\Implementation\DoctrineDBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Result;

/**
 * @extends AbstractDBALDataGetter<list<array<string,mixed>>, CompositeExpression|string|null, array<string, array{0: mixed, 1: int|string}>, array>
 */
class DoctrineDBALDataGetter extends AbstractDBALDataGetter
{
    private string $sql;

    public function __construct(
        string $sql,
        Connection $connection,
    ) {
        parent::__construct(
            $connection,
        );
        $this->sql = $sql;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getListResult(
        $expression,
        $parameters,
        $orderBy,
        ?int $limit = null,
        ?int $offset = null,
    ) {
        $sql = $this->getSqlWithExpression($expression);

        $orderByString = $this->prepareOrderByString($orderBy);

        if ($orderByString !== null) {
            $this->injectOrderByIntoSql($sql, $orderByString);
        }

        if (
            $limit !== null ||
            $offset !== null
        ) {
            $sql = $this->modifyLimitQuery($sql, $limit, $offset);
        }

        return $this->executeWithParameters($sql, $parameters)->fetchAllAssociative();
    }

    private function getSqlWithExpression(CompositeExpression|string|null $mappedExpression): string
    {
        if ($mappedExpression === null) {
            return $this->sql;
        }

        return $this->injectWhereIntoSql($this->sql, (string)$mappedExpression);
    }

    /**
     * @param string $query
     * @param array<string, array{0: mixed, 1: int|string}>|null $parameters
     * @return Result
     * @throws Exception
     */
    private function executeWithParameters(string $query, array|null $parameters): Result
    {
        $params = [];
        $paramTypes = [];

        if ($parameters !== null) {
            foreach($parameters as $parameterName => $parameterValueAndType) {
                $params[$parameterName] = $parameterValueAndType[0];
                $paramTypes[$parameterName] = $parameterValueAndType[1];
            }
        }

        return $this->connection->executeQuery(
            $query,
            $params,
            $paramTypes,
        );
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getScalarResult($expression, $parameters): mixed
    {
        $sql = $this->getSqlWithExpression($expression);
        return $this->executeWithParameters($sql, $parameters)->fetchOne();
    }
}