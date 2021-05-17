<?php
namespace App\Rabbitmq\Mod;

use App\Rabbitmq\Processor\RPC\RpcServerProcessor;
use Monolog\Logger;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;
use Swarrot\Consumer as SConsumer;

class RpcServer
{

    /**
     *
     * @var Pimple\Container
     */
    private $_dic;
    /**
     *
     * @var LoggerInterface
     */
    private $logger;
    /**
     *
     * @var string
     */
    private $connection;
    /**
     *
     * @var \AMQPChannel
     */
    private $channel;
    /**
     *
     * @var \AMQPQueue
     */
    private $queue;
    /**
     *
     * @var \AMQPExchange
     */
    private $exchange;
    /**
     *
     * @var callable
     */
    private $callback;

    public function __construct($con_params, $logger = null)
    {
        $this->logger        = $logger ?? new Logger('rpc-server');
        $con_params['login'] = $con_params['user'];
        $this->connection    = new \AMQPConnection($con_params);
        $this->connection->connect();
        $this->channel = new \AMQPChannel($this->connection);
    }
    /**
     *
     * @param Pimple\Container $dic
     *
     * @return self
     */
    public function setDic($dic)
    {
        $this->_dic = $dic;
        return $this;
    }
    /**
     *
     * @param array $config
     *
     * @return self
     */

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
    /**
     *
     * @param string $name
     *
     * @return self
     */

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
        $cb = new $callback();
        $cb->setDic($this->_dic);

        $processor = $stack->resolve(new RpcServerProcessor($cb, $messagePub, $this->logger));
        $consumer  = new SConsumer($messageProvider, $processor, null, $this->logger);
        $consumer->consume([]);
    }

}
