<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CloudShop\Database;
use CloudShop\RedisQueue;

$message = null;
$error = null;
$products = [];

try {
    $products = Database::connection()
        ->query('SELECT id, name, price, stock FROM products ORDER BY id')
        ->fetchAll();
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
    $customer = trim((string) ($_POST['customer'] ?? ''));
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if ($customer === '' || $productId === false || $quantity === false || $quantity < 1 || $quantity > 20) {
        $error = 'Enter a customer name, choose a product, and use a quantity from 1 to 20.';
    } else {
        try {
            $requestId = bin2hex(random_bytes(8));
            $queueLength = (new RedisQueue())->push([
                'request_id' => $requestId,
                'customer' => $customer,
                'product_id' => $productId,
                'quantity' => $quantity,
                'submitted_at' => gmdate(DATE_ATOM),
            ]);
            $message = sprintf('Order %s was added to Redis. Queue length: %d.', $requestId, $queueLength);
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submit an order</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Submit an order</h1>
    <nav><a href="index.php">Home</a><a href="process.php">Order status</a></nav>
</header>
<main>
    <section class="card">
        <p>This page sends an order message to the Redis FIFO queue. The worker container processes it asynchronously.</p>
        <?php if ($message !== null): ?><p class="ok"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

        <form method="post">
            <label for="customer">Customer name</label>
            <input id="customer" name="customer" required maxlength="100" value="<?= e((string) ($_POST['customer'] ?? '')) ?>">

            <label for="product_id">Product</label>
            <select id="product_id" name="product_id" required>
                <option value="">Select a product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= e((string) $product['id']) ?>">
                        <?= e(sprintf('%s — $%.2f — stock %d', $product['name'], $product['price'], $product['stock'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="quantity">Quantity</label>
            <input id="quantity" name="quantity" type="number" min="1" max="20" value="1" required>

            <button type="submit">Add order to queue</button>
        </form>
    </section>
</main>
</body>
</html>
