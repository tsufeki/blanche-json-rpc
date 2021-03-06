<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Message;

use Tsufeki\BlancheJsonRpc\Exception\JsonRpcException;

class ErrorResponse extends Response
{
    /**
     * @var JsonRpcException
     */
    public $error;

    /**
     * @param string|int|float|null $id
     */
    public function __construct($id = null, JsonRpcException $error = null)
    {
        $this->id = $id;
        if ($error !== null) {
            $this->error = $error;
        }
    }
}
