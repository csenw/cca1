<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CloudShop\Database;
use CloudShop\RedisQueue;

header('Content-Type: application/json; charset=utf-8');

$status = [
    'status' => 'ok',
    'php' => PHP_VERSION,
    'database' => false,
    'redis' => false,
    'timestamp' => gmdate(DATE_ATOM),
];

try {
    Database::connection()->query('SELECT 1');
    $status['database'] = true;
    $status['redis'] = (new RedisQueue())->ping();
} catch (Throwable $exception) {
    $status['status'] = 'error';
    $status['message'] = $exception->getMessage();
}

if (!$status['database'] || !$status['redis']) {
    $status['status'] = 'error';
}

http_response_code($status['status'] === 'ok' ? 200 : 503);
echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
