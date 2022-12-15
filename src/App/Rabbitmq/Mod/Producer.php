<?php

namespace App\Rabbitmq\Mod;

use Monolog\Logger;
use Swarrot\Broker\Message;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;

class Producer
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

    public function __construct($con_params)
    {
        $this->logger        = new Logger('producer');
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
        $this->exchange->setName($config['name']);
        $this->exchange->setType($config['type'] ?? AMQP_EX_TYPE_TOPIC);
        $this->exchange->setFlags($config['flags'] ?? AMQP_DURABLE);
        $this->exchange->setArguments($config);
        $this->exchange->declareExchange();
        return $this;
    }

    /**
     *
     * @param string $msg
     * @param string $routingKey
     * @param array $msg_arguments
     *
     * @return self
     */
    public function publish($msg, $routingKey = '', $msg_arguments = [])
    {
        $msgBody  = \is_array($msg) ? \json_encode($msg) : $msg;
        $provider = new PeclPackageMessagePublisher($this->exchange, AMQP_NOPARAM, $this->logger);
        $provider->publish(new Message($msgBody), $routingKey);
        return $this;
    }

}
