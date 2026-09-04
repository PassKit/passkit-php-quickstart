<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

final class Rpc
{
    public static function unary(object $call): object
    {
        [$response, $status] = $call->wait();
        self::assertStatus($status);
        return $response;
    }

    public static function assertStatus(object $status): void
    {
        if (($status->code ?? 0) !== 0) {
            throw new \RuntimeException(sprintf(
                'PassKit API error %s: %s',
                $status->code,
                $status->details ?? 'Unknown error',
            ), (int) $status->code);
        }
    }
}
