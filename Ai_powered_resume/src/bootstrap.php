<?php

// Basic bootstrap for DB connection and autoloading

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'resume_tailor');
define('DB_USER', 'root');
define('DB_PASS', '');

autoload();

function autoload()
{
    spl_autoload_register(function ($class) {
        $path = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
        $path = str_replace('src/', '', $path);
        $path = str_replace('\\', '/', $path);
        if (file_exists($path)) {
            require_once $path;
        }
    });
}

function get_db()
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
