<?php
declare(strict_types=1);

namespace Ergnuor\DataGetter\Implementation\DoctrineORM;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Parameter;

/**
 * @extends AbstractORMDataGetter<iterable, Composite|Comparison|null, iterable<Parameter>, array>
 */
class DoctrineORMNativeQueryDataGetter extends AbstractORMDataGetter
{
    private NativeQuery $query;

    public function __construct(
        NativeQuery $query,
        int $listHydrationMode = AbstractQuery::HYDRATE_OBJECT,
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
            $query->setSQL(
                $this->injectOrderByIntoSql($query->getSQL(), $orderByString)
            );
        }

        if (
            $limit !== null ||
            $offset !== null
        ) {
            $query->setSQL($this->modifyLimitQuery($query->getSQL(), $limit, $offset));
        }

        return $query->getResult($this->listHydrationMode);
    }

    /**
     * @param Composite|Comparison|null $mappedExpression
     * @param iterable<Parameter>|null $parameters
     * @return NativeQuery
     */
    private function getQueryWithExpression(
        Composite|Comparison|null $mappedExpression,
        iterable|null $parameters,
    ): NativeQuery {
        $queryClone = $this->cloneQuery($this->query);

        if ($mappedExpression === null) {
            return $queryClone;
        }

        $queryClone->setSQL(
            $this->injectWhereIntoSql($queryClone->getSQL(), (string)$mappedExpression)
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

    private function cloneQuery(NativeQuery $query): NativeQuery
    {
        $cloneQuery = clone $query;

        $cloneQuery->setParameters(clone $query->getParameters());
        $cloneQuery->setCacheable(false);

        foreach ($query->getHints() as $name => $value) {
            $cloneQuery->setHint($name, $value);
        }

        return $cloneQuery;
    }

    /**
     * @inheritDoc
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getScalarResult($expression, $parameters): mixed
    {
        $query = $this->getQueryWithExpression($expression, $parameters);
        return $query->getSingleScalarResult();
    }
}