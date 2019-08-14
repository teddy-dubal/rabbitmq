<?php

namespace App\Rabbitmq\Mod;

use Monolog\Logger;
use Swarrot\Broker\Message;
use Swarrot\Consumer as SConsumer;
use Swarrot\Processor\RPC\RpcClientProcessor;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;

class RpcClient {

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
        $this->exchange->setName('local-exchange');
        $this->exchange->setType($config['type'] ?? AMQP_EX_TYPE_DIRECT);
        $this->exchange->setFlags($config['flags'] ?? AMQP_DURABLE);
        $this->exchange->setArguments($config);
        $this->exchange->declare();
        return $this;
    }

    public function initClient(){
        $this->queue = new \AMQPQueue($this->channel);
        $this->queue->setName('local-queue');
        // $this->queue->setFlags($config['flags'] ?? AMQP_DURABLE);
        //$this->queue->setArguments($config);
        // $this->queue->declare();
        return $this;
    }
    
    public function addRequest($msgBody, $routingKey = '', $msg_arguments = [])
    {
        $provider = new PeclPackageMessagePublisher($this->exchange);
        $msg = new Message($msgBody, ['correlation_id'=>'wep']);
        $provider->publish(
            $msg, $routingKey
        );
    }

    public function getReplies()
    {
        // $messageProvider = new PeclPackageMessageProvider($this->queue);
        // $logger          = new Logger('rabbit');
        // $consumer  = new SConsumer($messageProvider, new RpcClientProcessor(null,$logger),null,$logger);
        // $result = $consumer->consume(['rpc_client_correlation_id'=>'yoyo']);
        // var_dump($result);

    }
}
