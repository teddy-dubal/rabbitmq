<?php

namespace App\Rabbitmq\Workers;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class debugRpcCallback implements ProcessorInterface
{
    private $_dic;

    public function process(Message $message, array $options)
    {
        return '[' . date('Y-m-d H:i:s') . '] ' . $message->getBody();
    }
    public function setDic($dic)
    {
        $this->_dic = $dic;
    }
}
