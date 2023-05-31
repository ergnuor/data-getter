<?php
declare(strict_types=1);

namespace Ergnuor\DataGetter\Implementation\DoctrineORM;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends AbstractORMDataGetter<Paginator<mixed>, Composite|Comparison|null, iterable<Parameter>, array>
 */
class DoctrineORMQueryDataGetter extends AbstractORMDataGetter
{
    private Query|QueryBuilder $query;

    public function __construct(
        Query $query,
        int $listHydrationMode = AbstractQuery::HYDRATE_ARRAY,
    ) {
        parent::__construct(
            $listHydrationMode
        );
        $this->query = $query;
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
        $query = $this->getQueryWithExpression($expression, $parameters);

        $orderByString = $this->prepareOrderByString($orderBy);

        if ($orderByString !== null) {
            $query->setDQL(
                $this->injectOrderByIntoSql($query->getDQL(), $orderByString)
            );
        }

        if ($offset !== null) {
            $query->setFirstResult($offset);
        }

        if ($limit !== null) {
            $query->setMaxResults($limit);
        }

        $query->setHydrationMode($this->listHydrationMode);
        return new Paginator($query);
    }

    /**
     * @param Composite|Comparison|null $mappedExpression
     * @param iterable<Parameter>|null $parameters
     * @return Query
     */
    private function getQueryWithExpression(
        Composite|Comparison|null $mappedExpression,
        iterable|null $parameters,
    ): Query {
        $queryClone = $this->cloneQuery($this->query);

        if ($mappedExpression === null) {
            return $queryClone;
        }

        $queryClone->setDQL(
            $this->injectWhereIntoSql($queryClone->getDQL(), (string)$mappedExpression)
        );

        if ($parameters !== null) {
            foreach ($parameters as $parameter) {
                $queryClone->setParameter(
                    $parameter->getName(),
                    $parameter->getValue(),
                    $parameter->getType(),
                );
            }
        }

        return $queryClone;
    }

    private function cloneQuery(Query $query): Query
    {
        $queryClone = clone $query;

        $queryClone->setParameters(clone $query->getParameters());
        $queryClone->setCacheable(false);

        foreach ($query->getHints() as $name => $value) {
            $queryClone->setHint($name, $value);
        }

        return $queryClone;
    }

    /**
     * @inheritDoc
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getScalarResult($expression, $parameters): mixed
    {
        return $this->getQueryWithExpression($expression, $parameters)->getSingleScalarResult();
    }
}