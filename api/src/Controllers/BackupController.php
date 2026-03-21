<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BackupController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function database(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = Database::getInstance($this->config['db']);
        $dbName = (string) ($this->config['db']['name'] ?? 'database');

        $stmtTables = $pdo->query('SHOW TABLES');
        $tables = $stmtTables->fetchAll(PDO::FETCH_NUM);

        $output = '';
        $output .= '-- Backup generated at: ' . date('Y-m-d H:i:s') . "\n";
        $output .= '-- Database: ' . $dbName . "\n\n";

        foreach ($tables as $row) {
            $table = (string) ($row[0] ?? '');
            if ($table === '') {
                continue;
            }

            $output .= '-- Table: `' . $table . "`\n\n";
            $output .= 'DROP TABLE IF EXISTS `' . $table . '`;' . "\n";

            $createStmt = $pdo->query('SHOW CREATE TABLE `' . $table . '`');
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            if ($createRow && isset($createRow[1])) {
                $output .= (string) $createRow[1] . ";\n\n";
            }

            $dataStmt = $pdo->query('SELECT * FROM `' . $table . '`');
            $rowsData = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rowsData) {
                $output .= "\n";
                continue;
            }

            $columns = array_keys($rowsData[0]);
            $colList = array_map(static fn (string $col): string => '`' . $col . '`', $columns);
            $output .= 'INSERT INTO `' . $table . '` (' . implode(', ', $colList) . ") VALUES\n";

            $valueLines = [];
            foreach ($rowsData as $dataRow) {
                $values = [];
                foreach ($columns as $col) {
                    $value = $dataRow[$col] ?? null;
                    if ($value === null) {
                        $values[] = 'NULL';
                        continue;
                    }

                    $escaped = str_replace(
                        ["\\", "\0", "\n", "\r", "'", '"', "\x1a"],
                        ["\\\\", "\\0", "\\n", "\\r", "\\'", '\\"', "\\Z"],
                        (string) $value
                    );
                    $values[] = "'" . $escaped . "'";
                }
                $valueLines[] = '(' . implode(', ', $values) . ')';
            }

            $output .= implode(",\n", $valueLines) . ";\n\n";
        }

        $filename = 'backup_' . $dbName . '_' . date('Ymd_His') . '.sql';
        $response->getBody()->write($output);

        return $response
            ->withHeader('Content-Type', 'application/sql; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string) strlen($output))
            ->withStatus(200);
    }
}
