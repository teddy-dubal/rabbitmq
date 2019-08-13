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

use Exception;
use Swarrot\Broker\Message;
use Swarrot\Consumer as SConsumer;
use Swarrot\Processor\ProcessorInterface;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;

class Consumer
{
#https://github.com/symfony/messenger/blob/master/Transport/AmqpExt/Connection.php

    private $_dic, $connection, $channel, $queue;

    public function __construct($con_params)
    {
        $con_params['login'] = $con_params['user'];
        $this->connection    = new \AMQPConnection($con_params);
        $this->connection->connect();
        $this->channel = new \AMQPChannel($this->connection);
    }

    public function setExchangeOptions($config)
    {
        $exchange = new \AMQPExchange($this->channel);
        $exchange->setName($config['name']);
        $exchange->setType($config['type'] ?? AMQP_EX_TYPE_TOPIC);
        $exchange->setFlags($config['flags'] ?? AMQP_DURABLE);
        $exchange->setArguments($config);
        $exchange->declare();
        return $this;
    }
    public function setQueueOptions($config)
    {
        $this->queue = new \AMQPQueue($this->channel);
        $this->queue->setName($config['name']);
        $this->queue->setFlags($config['flags'] ?? AMQP_DURABLE);
        $this->queue->setArguments($config);
        $this->queue->declare();
        return $this;
    }
    public function setCallback($call = [])
    {
        var_dump($call);
    }
    public function setRoutingKey($key)
    {
        var_dump($key);
    }

    public function consume()
    {
        $messageProvider = new PeclPackageMessageProvider($this->queue);
        $consumer        = new SConsumer($messageProvider, new Processor());
        $consumer->consume();

    }

    public function setDic($dic)
    {
        $this->_dic = $dic;
    }

    public function processMessage(AMQPMessage $msg)
    {
        try {
            $body = json_decode($msg->body, true);
            call_user_func($this->callback, $body, $msg->delivery_info, $this->_dic);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            $this->consumed++;
            $this->maybeStopConsumer($msg);
        } catch (Exception $e) {
            throw $e;
        }
    }

}
class Processor implements ProcessorInterface
{
    public function process(Message $message, array $options)
    {
        printf("Consume message #%d\n", $message->getId());
    }
}
