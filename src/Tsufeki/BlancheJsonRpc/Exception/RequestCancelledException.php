<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Exception;

class RequestCancelledException extends JsonRpcException
{
    const MESSAGE = 'Request cancelled';
}
