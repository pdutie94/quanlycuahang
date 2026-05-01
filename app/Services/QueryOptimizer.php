<?php

namespace App\Services;

/**
 * Query Optimization Helper for common performance patterns
 */
class QueryOptimizer
{
    /**
     * Build optimized IN clause with chunked processing for large arrays
     * Prevents "too many SQL variables" errors and improves query plan
     */
    public static function buildInClause(string $column, array $values, int $chunkSize = 1000): array
    {
        if (empty($values)) {
            return ['sql' => '1=0', 'params' => []]; // Always false
        }

        $chunks = array_chunk($values, $chunkSize);
        $conditions = [];
        $params = [];

        foreach ($chunks as $i => $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $conditions[] = "{$column} IN ({$placeholders})";
            $params = array_merge($params, $chunk);
        }

        return [
            'sql' => '(' . implode(' OR ', $conditions) . ')',
            'params' => $params
        ];
    }

    /**
     * Optimize COUNT query by using index hints and avoiding full table scans
     */
    public static function buildOptimizedCount(
        string $table,
        array $conditions = [],
        array $params = [],
        ?string $indexHint = null
    ): array {
        $indexClause = $indexHint ? "USE INDEX ({$indexHint})" : '';
        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT COUNT(*) FROM {$table} {$indexClause} {$whereClause}";

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Build efficient pagination query with deferred join pattern
     * Reduces offset overhead for large datasets
     */
    public static function buildDeferredJoinPagination(
        string $table,
        string $idColumn,
        string $orderBy,
        int $limit,
        int $offset,
        array $selectColumns = ['*'],
        array $joins = [],
        array $conditions = [],
        array $params = []
    ): array {
        // Build WHERE clause
        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        // Build JOIN clauses
        $joinClause = '';
        foreach ($joins as $join) {
            $joinClause .= " {$join['type']} JOIN {$join['table']} ON {$join['on']}";
        }

        // Step 1: Get IDs with deferred join pattern
        $idSql = "SELECT t.{$idColumn}
                  FROM {$table} t
                  {$whereClause}
                  ORDER BY {$orderBy}
                  LIMIT ? OFFSET ?";

        // Step 2: Full data query
        $columns = implode(', ', $selectColumns);
        $fullSql = "SELECT {$columns}
                    FROM {$table} t
                    {$joinClause}
                    WHERE t.{$idColumn} IN (
                        SELECT {$idColumn} FROM (
                            {$idSql}
                        ) AS id_subq
                    )
                    ORDER BY {$orderBy}";

        $fullParams = array_merge($params, [$limit, $offset]);

        return [
            'id_sql' => $idSql,
            'sql' => $fullSql,
            'params' => $fullParams
        ];
    }

    /**
     * Build optimized EXISTS subquery instead of IN with large subquery
     * Better for large datasets
     */
    public static function buildExistsSubquery(
        string $outerTable,
        string $outerColumn,
        string $subqueryTable,
        string $subqueryColumn,
        array $subqueryConditions = [],
        array $subqueryParams = []
    ): array {
        $whereClause = empty($subqueryConditions)
            ? ''
            : 'WHERE ' . implode(' AND ', $subqueryConditions);

        $sql = "EXISTS (
            SELECT 1 FROM {$subqueryTable}
            WHERE {$subqueryTable}.{$subqueryColumn} = {$outerTable}.{$outerColumn}
            {$whereClause}
        )";

        return ['sql' => $sql, 'params' => $subqueryParams];
    }

    /**
     * Optimize aggregation queries with covering index hints
     */
    public static function buildOptimizedAggregation(
        string $table,
        array $aggregations,
        string $groupBy = '',
        array $conditions = [],
        array $params = [],
        ?string $indexHint = null
    ): array {
        $aggClauses = [];
        foreach ($aggregations as $alias => $expression) {
            $aggClauses[] = "{$expression} AS {$alias}";
        }

        $indexClause = $indexHint ? "USE INDEX ({$indexHint})" : '';
        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $groupClause = $groupBy ? "GROUP BY {$groupBy}" : '';

        $sql = "SELECT " . implode(', ', $aggClauses) . "
                FROM {$table} {$indexClause}
                {$whereClause}
                {$groupClause}";

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Batch insert with chunked processing to avoid memory issues
     */
    public static function buildBatchInsert(
        string $table,
        array $columns,
        array $rows,
        int $chunkSize = 1000
    ): array {
        if (empty($rows)) {
            return [];
        }

        $chunks = array_chunk($rows, $chunkSize);
        $columnList = implode(', ', $columns);
        $placeholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $queries = [];
        foreach ($chunks as $chunk) {
            $placeholders = implode(', ', array_fill(0, count($chunk), $placeholder));
            $sql = "INSERT INTO {$table} ({$columnList}) VALUES {$placeholders}";

            $params = [];
            foreach ($chunk as $row) {
                foreach ($columns as $col) {
                    $params[] = $row[$col] ?? null;
                }
            }

            $queries[] = ['sql' => $sql, 'params' => $params];
        }

        return $queries;
    }

    /**
     * Optimize full-text search with pattern matching
     */
    public static function buildOptimizedSearch(
        string $table,
        array $searchableColumns,
        string $keyword,
        string $matchMode = 'prefix' // 'prefix', 'suffix', 'contains', 'exact'
    ): array {
        $conditions = [];
        $params = [];

        $pattern = match ($matchMode) {
            'prefix' => $keyword . '%',
            'suffix' => '%' . $keyword,
            'contains' => '%' . $keyword . '%',
            'exact' => $keyword,
            default => '%' . $keyword . '%'
        };

        foreach ($searchableColumns as $column) {
            $conditions[] = "{$column} LIKE ?";
            $params[] = $pattern;
        }

        return [
            'sql' => '(' . implode(' OR ', $conditions) . ')',
            'params' => $params
        ];
    }

    /**
     * Get query execution plan analysis
     */
    public static function analyzeQuery(\PDO $pdo, string $sql, array $params = []): array
    {
        $explainSql = 'EXPLAIN ' . $sql;
        $stmt = $pdo->prepare($explainSql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Check if table needs index optimization
     */
    public static function analyzeTableIndexes(\PDO $pdo, string $table): array
    {
        $stmt = $pdo->query("SHOW INDEX FROM {$table}");
        return $stmt->fetchAll();
    }

    /**
     * Build efficient date range condition with index optimization
     */
    public static function buildDateRangeCondition(
        string $column,
        ?string $startDate,
        ?string $endDate,
        bool $includeTime = true
    ): array {
        $conditions = [];
        $params = [];

        if ($startDate) {
            $conditions[] = "{$column} >= ?";
            $params[] = $includeTime ? $startDate . ' 00:00:00' : $startDate;
        }

        if ($endDate) {
            $conditions[] = "{$column} <= ?";
            $params[] = $includeTime ? $endDate . ' 23:59:59' : $endDate;
        }

        return [
            'sql' => empty($conditions) ? '1=1' : implode(' AND ', $conditions),
            'params' => $params
        ];
    }

    /**
     * Build optimized items count query using simpler LEFT JOINs instead of UNION ALL subquery
     * Replaces expensive: (SELECT order_id, COUNT(*) FROM order_items GROUP BY order_id UNION ALL ...)
     * With: LEFT JOIN (SELECT order_id, COUNT(*) as cnt FROM order_items GROUP BY order_id) AS oi_counts
     *       LEFT JOIN (SELECT order_id, COUNT(*) as cnt FROM order_manual_items GROUP BY order_id) AS omi_counts
     */
    public static function buildOptimizedItemsCountSelect(): array
    {
        $sql = 'SELECT o.*, c.name AS customer_name, c.phone AS customer_phone,
                COALESCE(oi_counts.cnt, 0) + COALESCE(omi_counts.cnt, 0) AS items_count
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN (
                    SELECT order_id, COUNT(*) AS cnt
                    FROM order_items
                    GROUP BY order_id
                ) oi_counts ON oi_counts.order_id = o.id
                LEFT JOIN (
                    SELECT order_id, COUNT(*) AS cnt
                    FROM order_manual_items
                    GROUP BY order_id
                ) omi_counts ON omi_counts.order_id = o.id';

        return ['sql' => $sql, 'params' => []];
    }

    /**
     * Build optimized sold_qty query using product_sales_summary table if available
     * Falls back to subquery only when necessary
     */
    public static function buildOptimizedSoldQtyJoin(bool $useSummaryTable = true): string
    {
        if ($useSummaryTable) {
            return "LEFT JOIN product_sales_summary s ON s.product_id = p.id";
        }

        // Fallback to subquery with optimized conditions
        return "LEFT JOIN (
            SELECT oi.product_id, SUM(oi.qty_base) AS sold_qty
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.deleted_at IS NULL
              AND (o.order_status IS NULL OR o.order_status != 'cancelled')
            GROUP BY oi.product_id
        ) s ON s.product_id = p.id";
    }

    /**
     * Build cursor-based pagination query for large datasets
     * Uses keyset pagination instead of OFFSET for better performance
     */
    public static function buildCursorPagination(
        string $table,
        string $idColumn,
        string $orderColumn,
        string $orderDirection = 'DESC',
        int $limit = 20,
        ?array $cursor = null // ['id' => lastId, 'value' => lastValue]
    ): array {
        $params = [];
        $whereClause = '';

        if ($cursor !== null && isset($cursor['id'], $cursor['value'])) {
            $operator = $orderDirection === 'DESC' ? '<' : '>';
            $whereClause = "WHERE ({$orderColumn} {$operator} ? OR ({$orderColumn} = ? AND {$idColumn} {$operator} ?))";
            $params = [$cursor['value'], $cursor['value'], $cursor['id']];
        }

        $sql = "SELECT * FROM {$table}
                {$whereClause}
                ORDER BY {$orderColumn} {$orderDirection}, {$idColumn} {$orderDirection}
                LIMIT ?";

        $params[] = $limit;

        return [
            'sql' => $sql,
            'params' => $params,
            'next_cursor' => null // Will be set after fetching results
        ];
    }

    /**
     * Build batch update query with CASE statement for efficient bulk updates
     */
    public static function buildBatchUpdate(
        string $table,
        string $idColumn,
        array $updateColumn,
        array $rows // Each row: ['id' => x, 'col1' => y, 'col2' => z]
    ): array {
        if (empty($rows)) {
            return ['sql' => '', 'params' => []];
        }

        $ids = array_column($rows, $idColumn);
        $caseStatements = [];
        $params = [];

        foreach ($updateColumn as $col) {
            $cases = [];
            foreach ($rows as $row) {
                $cases[] = "WHEN ? THEN ?";
                $params[] = $row[$idColumn];
                $params[] = $row[$col] ?? null;
            }
            $caseStatements[] = "{$col} = CASE {$idColumn} " . implode(' ', $cases) . " END";
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$table} SET " . implode(', ', $caseStatements) . " WHERE {$idColumn} IN ({$placeholders})";
        $params = array_merge($params, $ids);

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Execute batch operations with chunked processing
     */
    public static function executeBatch(
        \PDO $pdo,
        array $queries, // Array of ['sql' => ..., 'params' => ...]
        int $chunkSize = 1000
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $chunks = array_chunk($queries, $chunkSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $query) {
                try {
                    $stmt = $pdo->prepare($query['sql']);
                    $stmt->execute($query['params'] ?? []);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
            }
        }

        return $results;
    }
}
