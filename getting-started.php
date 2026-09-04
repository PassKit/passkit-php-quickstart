<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use PassKit\Quickstart\Config;
use PassKit\Quickstart\ConnectionPool;
use PassKit\Quickstart\CouponsQuickstart;
use PassKit\Quickstart\EventTicketsQuickstart;
use PassKit\Quickstart\FlightsQuickstart;
use PassKit\Quickstart\LoyaltyQuickstart;
use PassKit\Quickstart\PassKitApi;

$examples = [
    'loyalty' => ['membership/create-program.php', 'membership/create-tier.php', 'membership/enrol-member.php'],
    'coupons' => ['coupons/create-campaign.php', 'coupons/create-offer.php', 'coupons/create-coupon.php'],
    'tickets' => ['event-tickets/create-production.php', 'event-tickets/create-venue.php', 'event-tickets/create-event.php', 'event-tickets/issue-event-ticket.php'],
    'flights' => ['flights/create-carrier.php', 'flights/create-airport.php', 'flights/create-flight.php', 'flights/create-boarding-pass.php'],
];

$name = $argv[1] ?? null;
if ($name === null || in_array($name, ['help', '--help', '-h'], true)) {
    echo "PassKit PHP Quickstart\n\nUsage: composer example -- <loyalty|coupons|tickets|flights>\n\n";
    echo "Each choice runs a complete workflow and cleans up the resources it creates.\n";
    exit(0);
}
if (!isset($examples[$name])) {
    fwrite(STDERR, "Unknown example '{$name}'. Choose: " . implode(', ', array_keys($examples)) . "\n");
    exit(1);
}

try {
    $config = Config::fromEnvironment(__DIR__);
    $config->validate($name === 'flights');
    $pool = new ConnectionPool($config);
    try {
        $api = $pool->api();
        $methodCount = array_sum(array_map(
            fn (array $definition): int => count($definition['unary']) + count($definition['stream']),
            PassKitApi::METHODS,
        ));
        echo "Setup complete for {$name}. {$methodCount} safe SDK operations are available through PassKitApi.\n\n";
        $workflow = match ($name) {
            'loyalty' => new LoyaltyQuickstart($api, $config),
            'coupons' => new CouponsQuickstart($api, $config),
            'tickets' => new EventTicketsQuickstart($api, $config),
            'flights' => new FlightsQuickstart($api, $config),
        };
        try {
            $result = $workflow->run();
            echo "\nCreated resources:\n";
            foreach ($result->urls as $label => $url) {
                echo "  {$label}: {$url}\n";
            }
        } finally {
            if ($config->keepAssets) {
                echo "\nPASSKIT_KEEP_ASSETS=true; generated resources were not deleted.\n";
            } else {
                echo "\nCleaning up generated resources...\n";
                $workflow->cleanup();
            }
        }
    } finally {
        $pool->close();
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
