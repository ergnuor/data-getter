<?php
declare(strict_types=1);

namespace Ergnuor\DataGetter\Implementation\DoctrineDBAL;

use Doctrine\DBAL\Connection;
use Ergnuor\DataGetter\Implementation\SQL\AbstractSQLDataGetter;

/**
 * @template TListResult
 * @template TExpression
 * @template TParameters
 * @template TOrderBy
 * @extends  AbstractSQLDataGetter<TListResult, TExpression, TParameters, TOrderBy>
 */
abstract class AbstractDBALDataGetter extends AbstractSQLDataGetter
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        return $this->connection->getDriver()->getDatabasePlatform()->modifyLimitQuery($query, $limit, $offset);
    }
}