<?php

namespace App\Rabbitmq\Mod;

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

    public function setExchangeReady($bool = false)
    {
        $this->exchangeReady = $bool;
    }

    public function publish($msgBody, $routingKey = '', $msg_arguments = [])
    {
        if (!$this->exchangeReady) {
            //declare a durable non autodelete exchange
            $this->channel->exchange_declare($this->exchangeOptions['name'], $this->exchangeOptions['type'], $this->exchangeOptions['passive'], $this->exchangeOptions['durable'], $this->exchangeOptions['auto_delete'], $this->exchangeOptions['internal'], $this->exchangeOptions['nowait'], $this->exchangeOptions['arguments'], $this->exchangeOptions['ticket']);
            $this->exchangeReady = true;
        }

        $msg = new AMQPMessage($msgBody, array_merge(['content_type' => 'text/plain', 'delivery_mode' => 2], $msg_arguments));
        $this->channel->basic_publish($msg, $this->exchangeOptions['name'], $routingKey);
    }

}
