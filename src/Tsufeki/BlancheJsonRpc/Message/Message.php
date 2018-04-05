<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Message;

class Message
{
    const VERSION = '2.0';

    /**
     * @var string
     */
    public $jsonrpc = self::VERSION;
}
