<?php

declare(strict_types=1);

namespace PassKit\Quickstart\Tests;

use PassKit\Quickstart\CouponsQuickstart;
use PassKit\Quickstart\EventTicketsQuickstart;
use PassKit\Quickstart\FlightsQuickstart;
use PassKit\Quickstart\LoyaltyQuickstart;
use PassKit\Quickstart\QuickstartWorkflow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WorkflowTest extends TestCase
{
    #[DataProvider('workflows')]
    public function testEveryExampleHasACompleteWorkflow(string $class): void
    {
        self::assertTrue(is_subclass_of($class, QuickstartWorkflow::class));
    }

    public static function workflows(): iterable
    {
        yield 'loyalty' => [LoyaltyQuickstart::class];
        yield 'coupons' => [CouponsQuickstart::class];
        yield 'tickets' => [EventTicketsQuickstart::class];
        yield 'flights' => [FlightsQuickstart::class];
    }
}
