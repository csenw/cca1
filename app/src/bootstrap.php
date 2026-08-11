<?php

declare(strict_types=1);

$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoloadPath)) {
    throw new RuntimeException(
        'Composer dependencies are missing. Check the Dockerfile multi-stage build and vendor copy step.'
    );
}

require $autoloadPath;

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
