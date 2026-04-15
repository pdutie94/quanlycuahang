<?php

abstract class BaseRepository
{
    protected static function db(): PDO
    {
        return Database::getInstance();
    }

    /**
     * Find an active record by ID
     */
    protected static function findActiveRecordById(string $table, int $id)
    {
        $stmt = self::db()->prepare("SELECT * FROM {$table} WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
