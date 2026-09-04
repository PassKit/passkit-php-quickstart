<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

final class Env
{
    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if ($name === '' || getenv($name) !== false) {
                continue;
            }

            $value = trim($value, "\"'");
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}
