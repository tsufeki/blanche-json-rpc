<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Message;

class Response extends Message
{
    /**
     * @var mixed|null
     */
    public $result;

    /**
     * @var Error|null
     */
    public $error;

    /**
     * @var string|int|float|null
     */
    public $id;
}
