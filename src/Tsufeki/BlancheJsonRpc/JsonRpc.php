<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc;

use Recoil\Listener;
use Recoil\Recoil;
use Tsufeki\BlancheJsonRpc\Dispatcher\Dispatcher;
use Tsufeki\BlancheJsonRpc\Exception\JsonRpcException;
use Tsufeki\BlancheJsonRpc\Message\Error;
use Tsufeki\BlancheJsonRpc\Message\ErrorResponse;
use Tsufeki\BlancheJsonRpc\Message\Notification;
use Tsufeki\BlancheJsonRpc\Message\Request;
use Tsufeki\BlancheJsonRpc\Message\Response;
use Tsufeki\BlancheJsonRpc\Message\ResultResponse;
use Tsufeki\BlancheJsonRpc\Transport\Transport;
use Tsufeki\BlancheJsonRpc\Transport\TransportMessageObserver;
use Tsufeki\KayoJsonMapper\Mapper;

class JsonRpc implements TransportMessageObserver
{
    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var \Iterator
     */
    private $idSequence;

    /**
     * @var Listener[]
     */
    private $pendingRequests = [];

    public function __construct(Transport $transport, Dispatcher $dispatcher, Mapper $mapper)
    {
        $this->transport = $transport;
        $this->dispatcher = $dispatcher;
        $this->mapper = $mapper;

        $this->idSequence = (function (): \Generator {
            for ($i = 1;; $i++) {
                yield $i;
            }
        })();

        $this->transport->attach($this);
    }

    /**
     * @param string          $method
     * @param string          $returnType
     * @param array|\stdClass $args
     *
     * @resolve mixed
     */
    public function call(string $method, string $returnType, $args): \Generator
    {
        $request = new Request();
        $request->id = next($this->idSequence);
        $request->method = $method;
        $request->params = $args;

        $serializedRequest = Json::encode($this->mapper->dump($request));
        $this->pendingRequests[$request->id] = yield Recoil::strand();
        yield Recoil::execute($this->transport->send($serializedRequest));

        /** @var ResultResponse|ErrorResponse $response */
        $response = yield Recoil::suspend();

        if ($response instanceof ErrorResponse) {
            // TODO
            throw new JsonRpcException();
        }

        return $this->mapper->load($response->result, $returnType);
    }

    /**
     * @param string          $method
     * @param array|\stdClass $args
     *
     * @resolve void
     */
    public function notify(string $method, $args): \Generator
    {
        $notification = new Notification();
        $notification->method = $method;
        $notification->params = $args;

        $serializedNotification = Json::encode($this->mapper->dump($notification));
        yield Recoil::execute($this->transport->send($serializedNotification));
    }

    /**
     * @internal
     */
    public function receive(string $serializedMessage): \Generator
    {
        // TODO batch requests
        $messageType = implode('|', [
            Request::class,
            Notification::class,
            ResultResponse::class,
            ErrorResponse::class,
        ]);

        // TODO catch exceptions
        $message = $this->mapper->load(Json::decode($serializedMessage), $messageType);

        if ($message instanceof Response) {
            yield $this->handleResponse($message);
        } elseif ($message instanceof Request) {
            yield $this->handleRequest($message);
        } elseif ($message instanceof Notification) {
            yield $this->handleNotification($message);
        }
    }

    private function handleResponse(Response $response): \Generator
    {
        $pendingRequest = $this->pendingRequests[$response->id] ?? null;
        if ($pendingRequest) {
            unset($this->pendingRequests[$response->id]);
            $pendingRequest->send($response);
            // TODO else
        }

        return;
        yield;
    }

    private function handleRequest(Request $request): \Generator
    {
        try {
            $result = yield $this->dispatcher->dispatchRequest($request->method, $request->params);

            $response = new ResultResponse();
            $response->result = $result;
        } catch (\Throwable $e) {
            // TODO
            $response = new ErrorResponse();
            $response->error = new Error();
        }

        // TODO catch exceptions
        $response->id = $request->id;
        $serializedResponse = Json::encode($this->mapper->dump($response));
        yield $this->transport->send($serializedResponse);
    }

    private function handleNotification(Notification $notification): \Generator
    {
        try {
            yield $this->dispatcher->dispatchNotification($notification->method, $notification->params);
        } catch (\Throwable $e) {
            // TODO
        }
    }
}
