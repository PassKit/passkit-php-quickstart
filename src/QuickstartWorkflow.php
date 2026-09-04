<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

interface QuickstartWorkflow
{
    public function run(): QuickstartResult;

    public function cleanup(): void;
}
