<?php

declare(strict_types=1);

use Bliptrace\Php\Transport\Transport;

/**
 * In-memory transport that records every envelope it is asked to send.
 * Stands in for the real CurlTransport so tests never hit the network.
 */
final class FakeTransport implements Transport
{
    /** @var array<int,array{endpoint:string,key:string,envelope:array<string,mixed>}> */
    public array $sent = [];

    public function send(string $endpoint, string $key, array $envelope, int $timeout): void
    {
        $this->sent[] = compact('endpoint', 'key', 'envelope');
    }
}
