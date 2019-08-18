<?php

namespace App\Rabbitmq\Mod;

use Monolog\Logger;
use Swarrot\Broker\Message;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;
use Swarrot\Consumer as SConsumer;
use Swarrot\Processor\ProcessorInterface;
use Swarrot\Processor\RPC\RpcClientProcessor;

class RpcClient
{

    private $_dic;
    private $logger;
    private $connection;
    private $channel;
    private $queue;
    private $exchange;
    private $callback;
    private $correlation_id;
    private $reply_to;
    private $routing_key;

    public function __construct($con_params)
    {
        $this->logger        = new Logger('rpc-client');
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
        $this->exchange->setType($config['type'] ?? AMQP_EX_TYPE_DIRECT);
        $this->exchange->setFlags($config['flags'] ?? AMQP_DURABLE);
        $this->exchange->setArguments($config);
        return $this;
    }

    public function initClient($name)
    {
        $this->routing_key = $name;
        $this->queue       = new \AMQPQueue($this->channel);
        $this->queue->setName($name . '-queues');
        $this->queue->setArguments(['x-expires' => 1000]);
        $this->queue->declare();
        $this->reply_to = \uniqid('rcp_rp_');
        $this->queue->bind($this->exchange->getName(), $this->reply_to);
        return $this;
    }
    public function addRequest($msgBody)
    {
        $this->correlation_id = \uniqid('rcp_cid_');
        $provider             = new PeclPackageMessagePublisher($this->exchange, AMQP_NOPARAM, $this->logger);
        $msg                  = new Message($msgBody, ['correlation_id' => $this->correlation_id, 'reply_to' => $this->reply_to]);
        $provider->publish(
            $msg, $this->routing_key
        );
    }

    public function getReplies()
    {
        $messageProvider = new PeclPackageMessageProvider($this->queue);
        $rpc             = new Result();
        $consumer        = new SConsumer($messageProvider, new RpcClientProcessor($rpc, $this->logger), null, $this->logger);
        $consumer->consume(['rpc_client_correlation_id' => $this->correlation_id]);
        return $rpc->getBody();
    }
}
class Result implements ProcessorInterface
{
    private $result;
    public function process(Message $message, array $options)
    {
        $this->result = $message->getBody();
        return $this->result;
    }
    public function getBody()
    {
        return $this->result;
    }
}
