<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc;

use Recoil\Listener;
use Recoil\Recoil;
use Tsufeki\BlancheJsonRpc\Dispatcher\Dispatcher;
use Tsufeki\BlancheJsonRpc\Exception\InvalidRequestException;
use Tsufeki\BlancheJsonRpc\Exception\JsonException;
use Tsufeki\BlancheJsonRpc\Exception\JsonRpcException;
use Tsufeki\BlancheJsonRpc\Exception\ParseException;
use Tsufeki\BlancheJsonRpc\Exception\ServerException;
use Tsufeki\BlancheJsonRpc\Mapper\ExceptionDumper;
use Tsufeki\BlancheJsonRpc\Mapper\ExceptionLoader;
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
use Tsufeki\KayoJsonMapper\MapperBuilder;
use Tsufeki\KayoJsonMapper\MetadataProvider\NameMangler\NullNameMangler;

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

    public function __construct(Transport $transport, Dispatcher $dispatcher, Mapper $mapper, \Iterator $idSequence)
    {
        $this->transport = $transport;
        $this->dispatcher = $dispatcher;
        $this->mapper = $mapper;
        $this->idSequence = $idSequence;

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
        $request->id = next($this->idSequence);
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
        $messageType = implode('|', [
            Request::class,
            Notification::class,
            ResultResponse::class,
            ErrorResponse::class,
        ]);
        $batchType = $messageType . '|(' . $messageType . ')[]';

        /** @var Response|Response[]|null $response */
        $response = null;

        try {
            /** @var Message|Message[] $message */
            $message = $this->mapper->load(Json::decode($serializedMessage), $batchType);
            if (empty($message)) {
                $response = new ErrorResponse(null, new InvalidRequestException());
            } elseif (is_array($message)) {
                $response = yield array_map(function (Message $m) {
                    return $this->handleMessage($m);
                }, $message);

                $response = array_filter($response);
            } else {
                $response = yield $this->handleMessage($message);
            }
        } catch (JsonException $e) {
            $response = new ErrorResponse(null, new ParseException());
        } catch (MapperException $e) {
            $response = new ErrorResponse(null, new InvalidRequestException());
        }

        if (!empty($response)) {
            $serializedResponse = Json::encode($this->mapper->dump($response));
            yield $this->transport->send($serializedResponse);
        }
    }

    /**
     * @resolve Response|null
     */
    private function handleMessage(Message $message): \Generator
    {
        if ($message instanceof Response) {
            return yield $this->handleResponse($message);
        }
        if ($message instanceof Request) {
            return yield $this->handleRequest($message);
        }
        if ($message instanceof Notification) {
            return yield $this->handleNotification($message);
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
            $result = yield $this->dispatcher->dispatch($request->method, $request->params);

            $response = new ResultResponse($request->id, $result);
        } catch (JsonRpcException $e) {
            $response = new ErrorResponse($request->id, $e);
        } catch (\Throwable $e) {
            $response = new ErrorResponse($request->id, new ServerException());
            // TODO log
        }

        return $response;
    }

    private function handleNotification(Notification $notification): \Generator
    {
        try {
            yield $this->dispatcher->dispatch($notification->method, $notification->params);
        } catch (\Throwable $e) {
            // TODO log
        }
    }

    public static function create(Transport $transport, Dispatcher $dispatcher): self
    {
        $mapper = MapperBuilder::create()
            ->setNameMangler(new NullNameMangler())
            ->addDumper(new ExceptionDumper())
            ->addLoader(new ExceptionLoader())
            ->throwOnUnknownProperty(true)
            ->throwOnMissingProperty(true)
            ->getMapper();

        $idSequence = (function (): \Generator {
            for ($i = 1;; $i++) {
                yield $i;
            }
        })();

        return new static($transport, $dispatcher, $mapper, $idSequence);
    }
}
