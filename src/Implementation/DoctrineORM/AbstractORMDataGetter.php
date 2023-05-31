<?php
declare(strict_types=1);

namespace Ergnuor\DataGetter\Implementation\DoctrineORM;

use Doctrine\ORM\AbstractQuery;
use Ergnuor\DataGetter\Implementation\SQL\AbstractSQLDataGetter;

/**
 * @template TListResult
 * @template TExpression
 * @template TParameters
 * @template TOrderBy
 * @extends  AbstractSQLDataGetter<TListResult, TExpression, TParameters, TOrderBy>
 */
abstract class AbstractORMDataGetter extends AbstractSQLDataGetter
{
    protected int $listHydrationMode;

    public function __construct(
        int $listHydrationMode = AbstractQuery::HYDRATE_OBJECT,
    ) {
        $this->setListHydrationMode($listHydrationMode);
    }

    protected function setListHydrationMode($listHydrationMode): void
    {
        $allowedListHydrationMode = array_flip([AbstractQuery::HYDRATE_OBJECT, AbstractQuery::HYDRATE_ARRAY]);

        if (!isset($allowedListHydrationMode[$listHydrationMode])) {
            throw new \RuntimeException(
                sprintf(
                    "List hydration mode '%s' is not allowed",
                    $listHydrationMode
                )
            );
        }

        $this->listHydrationMode = $listHydrationMode;
    }
}