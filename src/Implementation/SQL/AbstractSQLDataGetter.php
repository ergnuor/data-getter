<?php
declare(strict_types=1);

namespace Ergnuor\DataGetter\Implementation\SQL;

use Ergnuor\DataGetter\DataGetterInterface;

/**
 * @template TListResult
 * @template TExpression
 * @template TParameters
 * @template TOrderBy
 * @implements DataGetterInterface<TListResult, TExpression, TParameters, TOrderBy>
 */
abstract class AbstractSQLDataGetter implements DataGetterInterface
{
    public const QUERY_WHERE_PLACEHOLDER = '::where::';
    public const QUERY_ORDER_BY_PLACEHOLDER = '::orderBy::';

    protected function modifyLimitQuery($query, $limit, $offset = 0): string
    {
        return $this->doModifyLimitQuery($query, $limit, $offset);
    }

    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        if ($limit !== null) {
            $query .= sprintf(' LIMIT %d', $limit);
        }

        if ($offset > 0) {
            $query .= sprintf(' OFFSET %d', $offset);
        }

        return $query;
    }

    protected function injectWhereIntoSql(string $sql, string $where): string
    {
        return $this->injectStringIntoQueryIntoSql(
            $sql,
            $where,
            self::QUERY_WHERE_PLACEHOLDER
        );
    }

    protected function injectOrderByIntoSql(string $sql, string $orderBy): string
    {
        return $this->injectStringIntoQueryIntoSql(
            $sql,
            $orderBy,
            self::QUERY_ORDER_BY_PLACEHOLDER
        );
    }

    private function injectStringIntoQueryIntoSql(string $query, string $replacement, string $placeholder): string
    {
        preg_match_all('/(?P<placeholder>' . preg_quote($placeholder) . ')/', $query, $m);

        if (count($m['placeholder']) > 1) {
            throw new \RuntimeException(
                sprintf(
                    "More than one '%s' placeholder found in query '%s'",
                    $placeholder,
                    $query
                )
            );
        }

        if (count($m['placeholder']) < 1) {
            throw new \RuntimeException(
                sprintf(
                    "'%s' placeholder not found in query '%s'",
                    $placeholder,
                    $query
                )
            );
        }

        return preg_replace(
            '/' . preg_quote($placeholder) . '/',
            $replacement,
            $query
        );
    }

    protected function prepareOrderByString(array|null $orderBy): string|null
    {
        if ($orderBy === null) {
            return null;
        }

        $orderByParts = [];

        foreach ($orderBy as $fieldName => $direction) {
            $orderByParts[] = $fieldName . ' ' . $direction;
        }

        return implode(', ', $orderByParts);
    }
}