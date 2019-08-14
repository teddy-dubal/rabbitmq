<?php
namespace App\Rabbitmq\Mod;
use Monolog\Logger;
use Swarrot\Consumer as SConsumer;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;

class RpcServer 
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
    
    /**
     * @param callable $callback
     * @throws \Exception
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }
    public function setRoutingKey($key)
    {
        $this->queue->bind($this->exchange->getName(), $key);
    }
    public function initServer($name){
        $this->queue = new \AMQPQueue($this->channel);
        $this->queue->setName($name.'-queue');
        $this->queue->setFlags( AMQP_DURABLE);
        //$this->queue->setArguments($config);
        $this->queue->declare();
        return $this;
    }
    public function start()
    {
        $messagePub = new PeclPackageMessagePublisher($this->exchange);
        $callback        = $this->callback;
        $logger          = new Logger('rabbit');
        $stack           = (new \Swarrot\Processor\Stack\Builder())
            ->push('Swarrot\Processor\MemoryLimit\MemoryLimitProcessor', $logger)
            ->push('Swarrot\Processor\MaxMessages\MaxMessagesProcessor', $logger)
            ->push('Swarrot\Processor\ExceptionCatcher\ExceptionCatcherProcessor', $logger)
            ->push('Swarrot\Processor\RPC\RpcServerProcessor',$messagePub,$logger)
        ;
        $processor = $stack->resolve(new $callback());
        $consumer  = new SConsumer($messagePub, $processor,null,$logger);
        $consumer->consume([]);
    }

}
