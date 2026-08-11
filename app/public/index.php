<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CloudShop\Database;
use CloudShop\RedisQueue;
use Faker\Factory;

$dbStatus = 'Unavailable';
$redisStatus = 'Unavailable';
$queueLength = null;
$fakeCustomer = 'Composer dependency unavailable';
$errors = [];

try {
    Database::connection()->query('SELECT 1');
    $dbStatus = 'Connected';
} catch (Throwable $exception) {
    $errors[] = $exception->getMessage();
}

try {
    $queue = new RedisQueue();
    $redisStatus = $queue->ping() ? 'Connected' : 'Unexpected response';
    $queueLength = $queue->length();
} catch (Throwable $exception) {
    $errors[] = $exception->getMessage();
}

try {
    $faker = Factory::create('en_AU');
    $fakeCustomer = $faker->name() . ' — ' . $faker->city();
} catch (Throwable $exception) {
    $errors[] = $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CloudShop Docker Test</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>CloudShop Docker Test</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="order.php">Submit order</a>
        <a href="process.php">Order status</a>
        <a href="health.php">Health JSON</a>
    </nav>
</header>
<main>
    <section class="card">
        <h2>Containerised PHP application</h2>
        <p>This page is served by Nginx and executed by the PHP-FPM application container.</p>
        <p>PHP version: <strong><?= e(PHP_VERSION) ?></strong></p>
        <p>Faker sample: <strong><?= e($fakeCustomer) ?></strong></p>
    </section>

    <section class="card">
        <h2>Service checks</h2>
        <p>MariaDB: <span class="<?= $dbStatus === 'Connected' ? 'ok' : 'error' ?>"><?= e($dbStatus) ?></span></p>
        <p>Redis: <span class="<?= $redisStatus === 'Connected' ? 'ok' : 'error' ?>"><?= e($redisStatus) ?></span></p>
        <p>Queued orders: <strong><?= $queueLength === null ? 'N/A' : e((string) $queueLength) ?></strong></p>
    </section>

    <?php if ($errors !== []): ?>
        <section class="card">
            <h2>Diagnostic messages</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li class="error"><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
