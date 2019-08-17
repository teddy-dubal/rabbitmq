<?php
namespace App\Rabbitmq\Mod;

use Monolog\Logger;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;
use Swarrot\Consumer as SConsumer;
use Swarrot\Processor\RPC\RpcServerProcessor;

class RpcServer
{

    private $_dic;
    private $logger;
    private $connection;
    private $channel;
    private $queue;
    private $exchange;
    private $callback;

    public function __construct($con_params)
    {
        $this->logger        = new Logger('rpc-server');
        $con_params['login'] = $con_params['user'];
        $this->connection    = new \AMQPConnection($con_params);
        $this->connection->connect();
        $this->channel = new \AMQPChannel($this->connection);
    }
    public function setDic($dic)
    {
        $this->_dic = $dic;
    }
    public function setExchangeOptions($config)
    {
        $this->exchange = new \AMQPExchange($this->channel);
        $this->exchange->setName($config['name'] ?? 'default-exchange');
        $this->exchange->setType($config['type'] ?? AMQP_EX_TYPE_TOPIC);
        $this->exchange->setFlags($config['flags'] ?? AMQP_DURABLE);
        $this->exchange->setArguments($config);
        $this->exchange->declare();
        return $this;
    }

    /**
     * @param callable $callback
     * @throws \Exception
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    public function initServer($name)
    {
        $this->queue = new \AMQPQueue($this->channel);
        $this->queue->setName($name . '-queue');
        $this->queue->setFlags(AMQP_DURABLE);
        $this->queue->declare();
        $this->queue->bind($this->exchange->getName(), $name);
        return $this;
    }
    public function start()
    {
        $messagePub      = new PeclPackageMessagePublisher($this->exchange);
        $messageProvider = new PeclPackageMessageProvider($this->queue);
        $callback        = $this->callback;
        $stack           = (new \Swarrot\Processor\Stack\Builder())
            ->push('Swarrot\Processor\ExceptionCatcher\ExceptionCatcherProcessor', $this->logger)
            ->push('Swarrot\Processor\Ack\AckProcessor', $messageProvider, $this->logger)
        ;
        $callback->setDic($this->_dic);
        $processor = $stack->resolve(new RpcServerProcessor(new $callback(), $messagePub, $this->logger));
        $consumer  = new SConsumer($messageProvider, $processor, null, $this->logger);
        $consumer->consume([]);
    }

}
