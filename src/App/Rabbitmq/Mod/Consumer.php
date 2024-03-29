<?php

/**
 * The MIT License
 *
 * Copyright (c) 2010 Alvaro Videla
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 *
 * @category   Thumper
 * @package    Thumper
 */

namespace App\Rabbitmq\Mod;

use Monolog\Logger;
use Pimple\Container;
use Psr\Log\LoggerInterface;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Consumer as SConsumer;

class Consumer
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
        $this->logger        = new Logger('consumer');
        $con_params['login'] = $con_params['user'];
        $this->connection    = new \AMQPConnection($con_params);
        $this->connection->connect();
        $this->channel = new \AMQPChannel($this->connection);
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
        $this->exchange->declare();
        return $this;
    }
    /**
     *
     * @param array $config
     *
     * @return self
     */
    public function setQueueOptions($config)
    {
        $this->queue = new \AMQPQueue($this->channel);
        $this->queue->setName($config['name']);
        $this->queue->setFlags($config['flags'] ?? AMQP_DURABLE);
        $this->queue->setArguments($config);
        $this->queue->declare();
        return $this;
    }
    /**
     *
     * @param callable $callback
     *
     * @return self
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }
    /**
     *
     * @param string $key
     *
     * @return self
     */
    public function setRoutingKey($key)
    {
        $this->queue->bind($this->exchange->getName(), $key);
        return $this;
    }

    public function consume()
    {
        $messageProvider = new PeclPackageMessageProvider($this->queue);
        $callback        = $this->callback;
        $stack           = (new \Swarrot\Processor\Stack\Builder())
            ->push('Swarrot\Processor\MemoryLimit\MemoryLimitProcessor', $this->logger)
            ->push('Swarrot\Processor\ExceptionCatcher\ExceptionCatcherProcessor', $this->logger)
            ->push('Swarrot\Processor\Ack\AckProcessor', $messageProvider)
        ;
        $cb = new $callback();
        $cb->setDic($this->_dic);
        $processor = $stack->resolve($cb);
        $consumer  = new SConsumer($messageProvider, $processor, null, $this->logger);
        $consumer->consume([]);
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

}
