<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Message;

class Error
{
    /**
     * @var int
     */
    public $code;

    /**
     * @var string
     */
    public $message;

    /**
     * @var mixed|null
     */
    public $data;
}
