<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Message;

class Response extends Message
{
    /**
     * @var string|int|float|null
     */
    public $id;
}
