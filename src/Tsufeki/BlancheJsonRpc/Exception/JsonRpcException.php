<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Exception;

class JsonRpcException extends \Exception
{
    const CODE_MIN = 0;
    const CODE_MAX = 0;
    const MESSAGE = 'Error';

    /**
     * @var mixed
     */
    protected $data;

    public function __construct(string $message = null, int $code = null, $data = null, \Throwable $previous = null)
    {
        parent::__construct($message ?? static::MESSAGE, $code ?? static::CODE_MAX, $previous);
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
