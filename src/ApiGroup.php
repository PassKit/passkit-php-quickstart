<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

use BadMethodCallException;

final class ApiGroup
{
    /** @param list<string> $unary @param list<string> $stream */
    public function __construct(
        private readonly object $client,
        private readonly array $unary,
        private readonly array $stream = [],
        private readonly bool $enabled = true,
    ) {
    }

    /** @return list<string> */
    public function methods(): array
    {
        return [...$this->unary, ...$this->stream, ...array_map(fn ($m) => $m . 'ToArray', $this->stream)];
    }

    public function __call(string $name, array $arguments): mixed
    {
        $request = $arguments[0] ?? null;
        $requestlessStreams = ['streamCouponUpdates', 'streamCouponRedemptions', 'streamPassUpdates'];
        $baseName = str_ends_with($name, 'ToArray') ? substr($name, 0, -7) : $name;
        if ($request === null && !in_array($baseName, $requestlessStreams, true)) {
            throw new BadMethodCallException("{$name} requires a protobuf request object.");
        }
        if (!$this->enabled) {
            throw new \LogicException(
                "{$name} is an advanced bulk operation. Set PASSKIT_ALLOW_DESTRUCTIVE=true to enable it.",
            );
        }
        if (in_array($name, $this->unary, true)) {
            return Rpc::unary($this->client->{$name}($request));
        }
        if (str_ends_with($name, 'ToArray')) {
            $streamName = substr($name, 0, -7);
            if (in_array($streamName, $this->stream, true)) {
                $call = in_array($streamName, $requestlessStreams, true)
                    ? $this->client->{$streamName}()
                    : $this->client->{$streamName}($request);
                return GrpcStream::toArray($call);
            }
        }
        if (in_array($name, $this->stream, true)) {
            $call = in_array($name, $requestlessStreams, true)
                ? $this->client->{$name}()
                : $this->client->{$name}($request);
            return $call->responses();
        }
        throw new BadMethodCallException("Unknown PassKit API method: {$name}");
    }
}
