<?php

namespace App\Rabbitmq\Workers;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class debugRpcCallback implements ProcessorInterface
{
    private $_dic;
    private $msg;

    public function process(Message $message, array $options): bool
    {
        $this->msg = '[' . date('Y-m-d H:i:s') . '] ' . $message->getBody();
        return true;
    }

    public function result()
    {
        return $this->msg;
    }

    public function setDic($dic)
    {
        $this->_dic = $dic;
    }
}
