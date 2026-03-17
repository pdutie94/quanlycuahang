<?php

class BackupController extends Controller
{
    public function database()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();

        $dbName = defined('DB_NAME') ? DB_NAME : '';
        if ($dbName === '') {
            http_response_code(500);
            echo 'Missing database name.';
            exit;
        }

        $stmtTables = $pdo->query('SHOW TABLES');
        $tables = $stmtTables->fetchAll(PDO::FETCH_NUM);

        $output = '';
        $output .= '-- Backup generated at: ' . date('Y-m-d H:i:s') . "\n";
        $output .= '-- Database: ' . $dbName . "\n\n";

        foreach ($tables as $row) {
            $table = isset($row[0]) ? $row[0] : '';
            if ($table === '') {
                continue;
            }

            $output .= '-- Table: `' . $table . "`\n\n";
            $output .= 'DROP TABLE IF EXISTS `' . $table . '`;' . "\n";

            $createStmt = $pdo->query('SHOW CREATE TABLE `' . $table . '`');
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            if ($createRow && isset($createRow[1])) {
                $output .= $createRow[1] . ";\n\n";
            }

            $dataStmt = $pdo->query('SELECT * FROM `' . $table . '`');
            $rowsData = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rowsData) {
                $output .= "\n";
                continue;
            }

            $columns = array_keys($rowsData[0]);
            $colList = array_map(function ($col) {
                return '`' . $col . '`';
            }, $columns);
            $output .= 'INSERT INTO `' . $table . '` (' . implode(', ', $colList) . ") VALUES\n";

            $valueLines = [];
            foreach ($rowsData as $dataRow) {
                $values = [];
                foreach ($columns as $col) {
                    $value = isset($dataRow[$col]) ? $dataRow[$col] : null;
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $escaped = str_replace(["\\", "\0", "\n", "\r", "'", "\"", "\x1a"], ["\\\\", "\\0", "\\n", "\\r", "\\'", "\\\"", "\\Z"], $value);
                        $values[] = "'" . $escaped . "'";
                    }
                }
                $valueLines[] = '(' . implode(', ', $values) . ')';
            }

            $output .= implode(",\n", $valueLines) . ";\n\n";
        }

        $filename = 'backup_' . $dbName . '_' . date('Ymd_His') . '.sql';

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        echo $output;
        exit;
    }
}

