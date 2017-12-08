<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Message;

class Request extends Message
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var array|\stdClass
     */
    public $params = [];

    /**
     * @var string|int|float|null
     */
    public $id;
}
