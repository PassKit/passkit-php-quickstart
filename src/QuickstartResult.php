<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

final readonly class QuickstartResult
{
    /** @param array<string, string> $urls */
    public function __construct(public array $urls)
    {
    }
}
