<?php
declare(strict_types=1);

namespace Ergnuor\DataGetter;

/**
 * @template TListResult
 * @template TExpression
 * @template TParameters
 * @template TOrderBy
 */
interface DataGetterInterface
{
    /**
     * @param TExpression|null $expression
     * @param TParameters|null $parameters
     * @param TOrderBy|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return TListResult
     */
    public function getListResult(
        $expression,
        $parameters,
        $orderBy,
        ?int $limit = null,
        ?int $offset = null,
    );

    /**
     * @param TExpression|null $expression
     * @param TParameters|null $parameters
     * @return mixed
     */
    public function getScalarResult($expression, $parameters): mixed;
}