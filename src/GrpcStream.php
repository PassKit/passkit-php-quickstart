<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

final class GrpcStream
{
    /** @return list<object> */
    public static function toArray(object $call): array
    {
        $items = iterator_to_array($call->responses(), false);
        $status = $call->getStatus();
        Rpc::assertStatus($status);
        return $items;
    }
}
