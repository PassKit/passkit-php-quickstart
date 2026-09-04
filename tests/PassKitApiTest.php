<?php

declare(strict_types=1);

namespace PassKit\Quickstart\Tests;

use PassKit\Quickstart\PassKitApi;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PassKitApiTest extends TestCase
{
    private const CLIENTS = [
        'loyalty' => \Members\MembersClient::class,
        'coupons' => \Single_use_coupons\SingleUseCouponsClient::class,
        'eventTickets' => \Event_tickets\EventTicketsClient::class,
        'flights' => \Flights\FlightsClient::class,
        'templates' => \Io\TemplatesClient::class,
        'images' => \Io\ImagesClient::class,
        'analytics' => \Analytics\AnalyticsClient::class,
        'distribution' => \Io\DistributionClient::class,
        'messages' => \Io\MessagesClient::class,
        'integrations' => \Io\IntegrationsClient::class,
        'scanners' => \Io\UsersClient::class,
        'certificates' => \Io\CertificatesClient::class,
        'raw' => \Raw\RawClient::class,
    ];

    #[DataProvider('safeMethods')]
    public function testSafeMethodExistsInSdk(string $domain, string $method): void
    {
        self::assertTrue(method_exists(self::CLIENTS[$domain], $method), "Missing {$domain}.{$method}");
    }

    #[DataProvider('advancedMethods')]
    public function testAdvancedMethodExistsInSdk(string $domain, string $method): void
    {
        self::assertTrue(method_exists(self::CLIENTS[$domain], $method), "Missing advanced {$domain}.{$method}");
    }

    public function testProvidesTypedHelpersAddedInSdk162(): void
    {
        $methods = get_class_methods(PassKitApi::class);
        self::assertContains('batchUpdateMembers', $methods);
        self::assertContains('addMessage', $methods);
        self::assertContains('getMessages', $methods);
        self::assertContains('cancelMessage', $methods);
        self::assertContains('client', $methods);
    }

    public static function safeMethods(): iterable
    {
        foreach (PassKitApi::METHODS as $domain => $definition) {
            foreach ([...$definition['unary'], ...$definition['stream']] as $method) {
                yield "{$domain}.{$method}" => [$domain, $method];
            }
        }
    }

    public static function advancedMethods(): iterable
    {
        foreach (PassKitApi::ADVANCED as $domain => $methods) {
            foreach ($methods as $method) {
                yield "advanced.{$domain}.{$method}" => [$domain, $method];
            }
        }
    }
}
