<?php

declare(strict_types=1);

namespace CloudShop;

use Redis;
use RedisException;
use RuntimeException;

final class RedisQueue
{
    private Redis $client;
    private string $queueName;

    public function __construct()
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('The PHP Redis extension is not loaded.');
        }

        $host = self::env('REDIS_HOST', 'redis');
        $port = (int) self::env('REDIS_PORT', '6379');
        $this->queueName = self::env('REDIS_QUEUE', 'orders');
        $this->client = new Redis();

        try {
            $connected = $this->client->connect($host, $port, 2.5);
        } catch (RedisException $exception) {
            throw new RuntimeException('Unable to connect to Redis: ' . $exception->getMessage(), 0, $exception);
        }

        if (!$connected) {
            throw new RuntimeException('Unable to connect to Redis.');
        }
    }

    /** @param array<string, mixed> $message */
    public function push(array $message): int
    {
        $json = json_encode($message, JSON_THROW_ON_ERROR);
        return $this->client->rPush($this->queueName, $json);
    }

    /** @return array<string, mixed>|null */
    public function blockingPop(int $timeoutSeconds = 5): ?array
    {
        $result = $this->client->blPop([$this->queueName], $timeoutSeconds);
        if ($result === false || count($result) !== 2) {
            return null;
        }

        /** @var array<string, mixed> $message */
        $message = json_decode((string) $result[1], true, 512, JSON_THROW_ON_ERROR);
        return $message;
    }

    public function length(): int
    {
        return (int) $this->client->lLen($this->queueName);
    }

    public function ping(): bool
    {
        $reply = $this->client->ping();
        return $reply === true || $reply === '+PONG' || $reply === 'PONG';
    }

    private static function env(string $name, string $default): string
    {
        $value = getenv($name);
        return $value === false || $value === '' ? $default : $value;
    }
}
