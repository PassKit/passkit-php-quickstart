<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

final class ConnectionPool
{
    /** @var list<array<string, object>> */
    private array $connections = [];
    private int $next = 0;

    public function __construct(private readonly Config $config)
    {
        $size = $config->connectionMode === 'pool' ? $config->poolSize : 1;
        for ($index = 0; $index < $size; ++$index) {
            $this->connections[] = ClientFactory::create($config);
        }
    }

    public function api(): PassKitApi
    {
        $clients = $this->connections[$this->next];
        $this->next = ($this->next + 1) % count($this->connections);
        return new PassKitApi($clients, $this->config->allowDestructive);
    }

    public function close(): void
    {
        foreach ($this->connections as $clients) {
            foreach ($clients as $client) {
                if (method_exists($client, 'close')) {
                    $client->close();
                }
            }
        }
        $this->connections = [];
    }

    public function __destruct()
    {
        $this->close();
    }
}
