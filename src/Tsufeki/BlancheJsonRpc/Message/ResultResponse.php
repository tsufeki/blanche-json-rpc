<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Message;

class ResultResponse extends Response
{
    /**
     * @var mixed
     */
    public $result;

    /**
     * @param string|int|float|null $id
     * @param mixed                 $result
     */
    public function __construct($id, $result = null)
    {
        $this->id = $id;
        $this->result = $result;
    }
}
