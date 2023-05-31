<?php
declare(strict_types=1);

namespace Ergnuor\DataGetter\Implementation\DoctrineDBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @extends AbstractDBALDataGetter<list<array<string,mixed>>, CompositeExpression|string|null, array<string, array{0: mixed, 1: int|string}>, array>
 */
class DoctrineDBALQueryBuilderDataGetter extends AbstractDBALDataGetter
{
    private QueryBuilder $queryBuilder;

    public function __construct(
        QueryBuilder $queryBuilder,
        Connection $connection,
    ) {
        parent::__construct(
            $connection,
        );
        $this->queryBuilder = $queryBuilder;
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
        $queryBuilder = $this->getQueryBuilderWithExpression($expression, $parameters);

        if ($orderBy !== null) {
            foreach ($orderBy as $fieldName => $direction) {
                $queryBuilder->addOrderBy($fieldName, $direction);
            }
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param CompositeExpression|string|null $mappedExpression
     * @param array<string, array{0: mixed, 1: int|string}>|null $parameters
     * @return QueryBuilder
     */
    private function getQueryBuilderWithExpression(
        CompositeExpression|string|null $mappedExpression,
        array|null $parameters,
    ): QueryBuilder {
        $queryBuilderClone = clone $this->queryBuilder;

        if ($mappedExpression === null) {
            return $queryBuilderClone;
        }

        $queryBuilderClone->where($mappedExpression);

        if ($parameters !== null) {
            foreach ($parameters as $parameterName => $parameterValueAndType) {
                $queryBuilderClone->setParameter(
                    $parameterName,
                    $parameterValueAndType[0],
                    $parameterValueAndType[1],
                );
            }
        }

        return $queryBuilderClone;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getScalarResult($expression, $parameters): mixed
    {
        $queryBuilder = $this->getQueryBuilderWithExpression($expression, $parameters);
        return $queryBuilder->executeQuery()->fetchOne();
    }
}