<?php

namespace App\Rabbitmq\Mod;

use Monolog\Logger;
use Swarrot\Broker\Message;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;

class Producer
{

    private $_dic, $connection, $channel, $queue, $exchange, $callback;

    public function __construct($con_params)
    {
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
        $this->exchange->setName($config['name']);
        $this->exchange->setType($config['type'] ?? AMQP_EX_TYPE_TOPIC);
        $this->exchange->setFlags($config['flags'] ?? AMQP_DURABLE);
        $this->exchange->setArguments($config);
        $this->exchange->declare();
        return $this;
    }

    public function publish($msgBody, $routingKey = '', $msg_arguments = [])
    {
        $logger          = new Logger('rabbit');
        $provider = new PeclPackageMessagePublisher($this->exchange,AMQP_NOPARAM,$logger);
        $return   = $provider->publish(
            new Message($msgBody), $routingKey
        );
    }

}
