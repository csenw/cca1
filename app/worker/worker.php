<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CloudShop\Database;
use CloudShop\RedisQueue;

fwrite(STDOUT, "CloudShop worker started. Waiting for Redis messages...\n");

while (true) {
    try {
        $queue = new RedisQueue();
        $message = $queue->blockingPop(5);
        if ($message === null) {
            continue;
        }

        $requestId = (string) ($message['request_id'] ?? '');
        $customer = trim((string) ($message['customer'] ?? ''));
        $productId = (int) ($message['product_id'] ?? 0);
        $quantity = (int) ($message['quantity'] ?? 0);

        if ($requestId === '' || $customer === '' || $productId < 1 || $quantity < 1) {
            throw new RuntimeException('Invalid order message: ' . json_encode($message, JSON_THROW_ON_ERROR));
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        $duplicate = $pdo->prepare('SELECT id FROM orders WHERE request_id = :request_id');
        $duplicate->execute(['request_id' => $requestId]);
        if ($duplicate->fetchColumn() !== false) {
            $pdo->rollBack();
            fwrite(STDOUT, "Skipping duplicate request {$requestId}.\n");
            continue;
        }

        $productStatement = $pdo->prepare(
            'SELECT id, name, price, stock FROM products WHERE id = :id FOR UPDATE'
        );
        $productStatement->execute(['id' => $productId]);
        $product = $productStatement->fetch(PDO::FETCH_ASSOC);

        if ($product === false) {
            $pdo->rollBack();
            throw new RuntimeException("Product {$productId} does not exist.");
        }

        $status = ((int) $product['stock'] >= $quantity) ? 'completed' : 'rejected_out_of_stock';
        $totalPrice = (float) $product['price'] * $quantity;

        if ($status === 'completed') {
            $update = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id');
            $update->execute(['quantity' => $quantity, 'id' => $productId]);
        }

        $insert = $pdo->prepare(
            'INSERT INTO orders '
            . '(request_id, customer, product_id, product_name, quantity, unit_price, total_price, status) '
            . 'VALUES (:request_id, :customer, :product_id, :product_name, :quantity, :unit_price, :total_price, :status)'
        );
        $insert->execute([
            'request_id' => $requestId,
            'customer' => $customer,
            'product_id' => $productId,
            'product_name' => $product['name'],
            'quantity' => $quantity,
            'unit_price' => $product['price'],
            'total_price' => $totalPrice,
            'status' => $status,
        ]);

        $pdo->commit();
        fwrite(STDOUT, sprintf("Processed %s: %s (%d x %s).\n", $requestId, $status, $quantity, $product['name']));
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, '[' . gmdate(DATE_ATOM) . '] ' . $exception->getMessage() . "\n");
        sleep(2);
    }
}
