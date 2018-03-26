<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Message;

class Notification extends Message
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var array|\stdClass|null
     */
    public $params;
}
