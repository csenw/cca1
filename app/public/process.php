<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CloudShop\Database;
use CloudShop\RedisQueue;

$error = null;
$products = [];
$orders = [];
$queueLength = null;

try {
    $pdo = Database::connection();
    $products = $pdo->query('SELECT id, name, price, stock FROM products ORDER BY id')->fetchAll();
    $orders = $pdo->query(
        'SELECT request_id, customer, product_name, quantity, total_price, status, created_at '
        . 'FROM orders ORDER BY id DESC LIMIT 20'
    )->fetchAll();
    $queueLength = (new RedisQueue())->length();
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="5">
    <title>Processed orders</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Processed orders</h1>
    <nav><a href="index.php">Home</a><a href="order.php">Submit order</a></nav>
</header>
<main>
    <?php if ($error !== null): ?><p class="card error"><?= e($error) ?></p><?php endif; ?>

    <section class="card">
        <h2>Redis queue</h2>
        <p>Messages waiting: <strong><?= $queueLength === null ? 'N/A' : e((string) $queueLength) ?></strong></p>
        <p>This page refreshes every five seconds.</p>
    </section>

    <section class="card">
        <h2>Product inventory</h2>
        <table>
            <thead><tr><th>ID</th><th>Product</th><th>Price</th><th>Stock</th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= e((string) $product['id']) ?></td>
                    <td><?= e($product['name']) ?></td>
                    <td>$<?= e(number_format((float) $product['price'], 2)) ?></td>
                    <td><?= e((string) $product['stock']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Latest orders</h2>
        <table>
            <thead><tr><th>Request</th><th>Customer</th><th>Product</th><th>Qty</th><th>Total</th><th>Status</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><code><?= e($order['request_id']) ?></code></td>
                    <td><?= e($order['customer']) ?></td>
                    <td><?= e($order['product_name']) ?></td>
                    <td><?= e((string) $order['quantity']) ?></td>
                    <td>$<?= e(number_format((float) $order['total_price'], 2)) ?></td>
                    <td><?= e($order['status']) ?></td>
                    <td><?= e($order['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
