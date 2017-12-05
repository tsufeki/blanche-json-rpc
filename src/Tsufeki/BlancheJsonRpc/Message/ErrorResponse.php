<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Message;

class ErrorResponse extends Response
{
    /**
     * @var Error|null
     */
    public $error;
}
