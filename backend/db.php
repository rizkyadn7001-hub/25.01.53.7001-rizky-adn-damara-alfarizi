<?php
// db.php - simple PDO connection wrapper
declare(strict_types=1);

$DB_CONFIG = [
    'host' => '127.0.0.1',
    'port' => 3307,
    'dbname' => 'canteen_new_db',
    'user' => 'root',
    'pass' => '', // isi jika root ada password
    'charset' => 'utf8mb4',
];

function getPDO(): PDO {
    global $DB_CONFIG;
    $host = $DB_CONFIG['host'];
    $port = $DB_CONFIG['port'];
    $dbname = $DB_CONFIG['dbname'];
    $user = $DB_CONFIG['user'];
    $pass = $DB_CONFIG['pass'];
    $charset = $DB_CONFIG['charset'];

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    return new PDO($dsn, $user, $pass, $opts);
}
