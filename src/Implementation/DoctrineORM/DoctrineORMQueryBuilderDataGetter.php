<?php
declare(strict_types=1);

namespace Ergnuor\DataGetter\Implementation\DoctrineORM;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends AbstractORMDataGetter<Paginator<mixed>, Composite|Comparison|null, iterable<Parameter>, array>
 */
class DoctrineORMQueryBuilderDataGetter extends AbstractORMDataGetter
{
    private QueryBuilder $queryBuilder;

    public function __construct(
        QueryBuilder $queryBuilder,
        int $listHydrationMode = AbstractQuery::HYDRATE_ARRAY,
    ) {
        parent::__construct(
            $listHydrationMode
        );
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @inheritDoc
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

        $query = $queryBuilder->getQuery();
        $query->setHydrationMode($this->listHydrationMode);
        return new Paginator($query);
    }

    /**
     * @param Composite|Comparison|null $mappedExpression
     * @param iterable<Parameter>|null $parameters
     * @return QueryBuilder
     */
    private function getQueryBuilderWithExpression(
        Composite|Comparison|null $mappedExpression,
        iterable|null $parameters,
    ): QueryBuilder {
        $queryBuilderClone = clone $this->queryBuilder;

        if ($mappedExpression === null) {
            return $queryBuilderClone;
        }

        $queryBuilderClone->where($mappedExpression);

        if ($parameters !== null) {
            foreach ($parameters as $parameter) {
                $queryBuilderClone->setParameter(
                    $parameter->getName(),
                    $parameter->getValue(),
                    $parameter->getType(),
                );
            }
        }

        return $queryBuilderClone;
    }

    /**
     * @inheritDoc
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getScalarResult($expression, $parameters): mixed
    {
        $queryBuilder = $this->getQueryBuilderWithExpression($expression, $parameters);
        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}