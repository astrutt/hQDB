<?php
// Version: 1.1.0
// Last Updated: 2026-03-27

// db.php

class DB {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Hardcoded secure path outside the web root
        $dbPath = '/opt/hqdb/hqdb.sqlite';
        
        try {
            $this->pdo = new PDO("sqlite:" . $dbPath);
            
            // Throw exceptions on errors to catch them easily during dev/production
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Fetch results as associative arrays by default
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enforce Write-Ahead Logging and Foreign Keys for this connection
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            
        } catch (PDOException $e) {
            // In production, you might want to log this to a file rather than outputting to screen
            error_log("hQDB Connection Error: " . $e->getMessage());
            die("Database connection failed. Ensure /opt/hqdb/ is readable/writable by www-data.");
        }
    }

    // Returns the active PDO instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DB();
        }
        return self::$instance->pdo;
    }
}
