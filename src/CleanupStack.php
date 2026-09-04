<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

final class CleanupStack
{
    /** @var list<array{string, callable(): void}> */
    private array $steps = [];

    public function add(string $label, callable $action): void
    {
        $this->steps[] = [$label, $action];
    }

    public function run(): void
    {
        foreach (array_reverse($this->steps) as [$label, $action]) {
            try {
                $action();
                echo "  Deleted {$label}.\n";
            } catch (\Throwable $error) {
                fwrite(STDERR, "  Could not delete {$label}: {$error->getMessage()}\n");
            }
        }
        $this->steps = [];
    }
}
