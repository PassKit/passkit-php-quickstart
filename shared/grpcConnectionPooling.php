<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use PassKit\Quickstart\Config;
use PassKit\Quickstart\ConnectionPool;

$config = Config::fromEnvironment(dirname(__DIR__));
$pool = new ConnectionPool($config);

try {
    $api = $pool->api();
    echo sprintf(
        "Clients ready for %s using %s mode (%d connection%s).\n",
        $config->target(),
        $config->connectionMode,
        $config->connectionMode === 'pool' ? $config->poolSize : 1,
        ($config->connectionMode === 'pool' ? $config->poolSize : 1) === 1 ? '' : 's',
    );
    echo "Example: \$api->loyalty->getProgram(new Io\\Id(['id' => 'PROGRAM_ID']));\n";
} finally {
    $pool->close();
}
