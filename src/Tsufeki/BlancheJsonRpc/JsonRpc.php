<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Recoil\Listener;
use Recoil\Recoil;
use Tsufeki\BlancheJsonRpc\Dispatcher\Dispatcher;
use Tsufeki\BlancheJsonRpc\Dispatcher\MethodRegistry;
use Tsufeki\BlancheJsonRpc\Dispatcher\MethodRegistryDispatcher;
use Tsufeki\BlancheJsonRpc\Dispatcher\RawInvoker;
use Tsufeki\BlancheJsonRpc\Exception\InvalidRequestException;
use Tsufeki\BlancheJsonRpc\Exception\JsonException;
use Tsufeki\BlancheJsonRpc\Exception\JsonRpcException;
use Tsufeki\BlancheJsonRpc\Exception\ParseException;
use Tsufeki\BlancheJsonRpc\Exception\ServerException;
use Tsufeki\BlancheJsonRpc\Mapper\MapperFactory;
use Tsufeki\BlancheJsonRpc\Message\ErrorResponse;
use Tsufeki\BlancheJsonRpc\Message\Message;
use Tsufeki\BlancheJsonRpc\Message\Notification;
use Tsufeki\BlancheJsonRpc\Message\Request;
use Tsufeki\BlancheJsonRpc\Message\Response;
use Tsufeki\BlancheJsonRpc\Message\ResultResponse;
use Tsufeki\BlancheJsonRpc\Transport\Transport;
use Tsufeki\BlancheJsonRpc\Transport\TransportMessageObserver;
use Tsufeki\KayoJsonMapper\Exception\MapperException;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Listener[]
     */
    private $pendingRequests = [];

    public function __construct(
        Transport $transport,
        Dispatcher $dispatcher,
        Mapper $mapper,
        \Iterator $idSequence,
        LoggerInterface $logger = null
    ) {
        $this->transport = $transport;
        $this->dispatcher = $dispatcher;
        $this->mapper = $mapper;
        $this->idSequence = $idSequence;
        $this->logger = $logger ?? new NullLogger();

        $this->transport->attach($this);
    }

    /**
     * @param string          $method
     * @param array|\stdClass $args
     *
     * @resolve mixed
     */
    public function call(string $method, $args): \Generator
    {
        $request = new Request();
        $request->id = $this->idSequence->current();
        $this->idSequence->next();
        $request->method = $method;
        $request->params = $args;

        $serializedRequest = Json::encode($this->mapper->dump($request));
        $this->pendingRequests[$request->id] = yield Recoil::strand();
        yield Recoil::execute($this->transport->send($serializedRequest));

        /** @var ResultResponse|ErrorResponse $response */
        $response = yield Recoil::suspend();

        if ($response instanceof ErrorResponse) {
            throw $response->error;
        }

        return $response->result;
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
        /** @var Response|Response[]|null $response */
        $response = null;

        try {
            $message = Json::decode($serializedMessage);

            if (empty($message)) {
                $this->logger->warning('[jsonrpc] Empty message received');
                $response = new ErrorResponse(null, new InvalidRequestException());
            } elseif (is_array($message)) {
                $response = yield array_map(function ($m) {
                    return $this->handleMessage($m);
                }, $message);

                $response = array_values(array_filter($response));
            } else {
                $response = yield $this->handleMessage($message);
            }
        } catch (JsonException $e) {
            $this->logger->warning('[jsonrpc] Malformed JSON received');
            $response = new ErrorResponse(null, new ParseException());
        }

        if (!empty($response)) {
            $serializedResponse = Json::encode($this->mapper->dump($response));
            yield $this->transport->send($serializedResponse);
        }
    }

    /**
     * @param mixed $messageData
     *
     * @resolve Response|null
     */
    private function handleMessage($messageData): \Generator
    {
        $messageType = implode('|', [
            ErrorResponse::class,
            ResultResponse::class,
            Notification::class,
            Request::class,
        ]);

        try {
            /** @var Message $message */
            $message = $this->mapper->load($messageData, $messageType);

            if ($message->jsonrpc !== Message::VERSION) {
                $this->logger->warning('[jsonrpc] Bad JSONRPC version received');
            }
        } catch (MapperException $e) {
            $this->logger->warning('[jsonrpc] Malformed message received');

            return new ErrorResponse(null, new InvalidRequestException());
        }

        if ($message instanceof Response) {
            return yield $this->handleResponse($message);
        }
        if ($message instanceof Request) {
            return yield $this->handleRequest($message);
        }
        if ($message instanceof Notification) {
            return yield $this->handleNotification($message);
        }

        throw new \LogicException(); // @codeCoverageIgnore
    }

    private function handleResponse(Response $response): \Generator
    {
        $pendingRequest = $this->pendingRequests[$response->id] ?? null;
        if ($pendingRequest) {
            unset($this->pendingRequests[$response->id]);
            $pendingRequest->send($response);
        } else {
            $this->logger->error('[jsonrpc] Cannot match response to request'); // @codeCoverageIgnore
        }

        return;
        yield;
    }

    private function handleRequest(Request $request): \Generator
    {
        try {
            $result = yield $this->dispatcher->dispatchRequest($request->method, $request->params ?? []);

            $response = new ResultResponse($request->id, $result);
        } catch (JsonRpcException $e) {
            $this->logger->notice("[jsonrpc] Error during dispatching request $request->method", ['exception' => $e]);
            $response = new ErrorResponse($request->id, $e);
        } catch (\Throwable $e) {
            $this->logger->critical("[jsonrpc] Error during dispatching request $request->method", ['exception' => $e]);
            $response = new ErrorResponse($request->id, new ServerException());
        }

        return $response;
    }

    private function handleNotification(Notification $notification): \Generator
    {
        try {
            yield $this->dispatcher->dispatchNotification($notification->method, $notification->params ?? []);
        } catch (\Throwable $e) {
            $this->logger->critical("[jsonrpc] Error during dispatching notification $notification->method", ['exception' => $e]);
        }
    }

    public static function create(
        Transport $transport,
        MethodRegistry $methodRegistry,
        LoggerInterface $logger = null
    ): self {
        $mapper = (new MapperFactory())->create();
        $invoker = new RawInvoker();
        $dispatcher = new MethodRegistryDispatcher($methodRegistry, $invoker);
        $idSequence = (function (): \Generator {
            for ($i = 1;; $i++) {
                yield $i;
            }
        })();

        return new static(
            $transport,
            $dispatcher,
            $mapper,
            $idSequence,
            $logger
        );
    }
}
