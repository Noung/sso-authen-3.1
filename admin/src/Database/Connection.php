<?php

namespace SsoAdmin\Database;

use PDO;
use PDOException;

/**
 * Database Connection Manager
 * Compatible with PHP 7.4.33
 */
class Connection
{
    private static $pdo = null;
    private static $config = null;

    /**
     * Initialize database connection
     */
    public static function init(array $config)
    {
        self::$config = $config;
    }

    /**
     * Get PDO instance
     */
    public static function getPdo()
    {
        if (self::$pdo === null) {
            self::connect();
        }
        return self::$pdo;
    }

    /**
     * Create database connection
     */
    private static function connect()
    {
        if (self::$config === null) {
            throw new \Exception('Database configuration not initialized');
        }

        $config = self::$config;
        
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute a query
     */
    public static function query($sql, $params = [])
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all results
     */
    public static function fetchAll($sql, $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single result
     */
    public static function fetchOne($sql, $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId()
    {
        return self::getPdo()->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction()
    {
        return self::getPdo()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit()
    {
        return self::getPdo()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback()
    {
        return self::getPdo()->rollback();
    }

    /**
     * Test database connection
     */
    public static function testConnection()
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}