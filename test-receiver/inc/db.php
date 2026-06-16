<?php

require_once dirname(__DIR__) . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST, DB_PORT, DB_NAME
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS receiver_batches (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type        VARCHAR(20)  NOT NULL,
            count       INT UNSIGNED NOT NULL,
            ip          VARCHAR(45),
            token_hint  VARCHAR(20),
            received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_received_at (received_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS receiver_records (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            batch_id     INT UNSIGNED NOT NULL,
            container_no VARCHAR(20),
            payload      MEDIUMTEXT,
            sim_status   VARCHAR(20),
            FOREIGN KEY (batch_id) REFERENCES receiver_batches(id) ON DELETE CASCADE,
            INDEX idx_batch (batch_id),
            INDEX idx_container (container_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    return $pdo;
}
