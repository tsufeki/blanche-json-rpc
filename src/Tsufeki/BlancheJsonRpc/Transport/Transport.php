<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Transport;

interface Transport
{
    public function send(string $message): \Generator;

    public function attach(TransportMessageObserver $observer);
}
