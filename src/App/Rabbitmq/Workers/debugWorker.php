<?php

namespace App\Rabbitmq\Workers;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class debugWorker implements ProcessorInterface
{
    private $_dic;

    public function process(Message $message, array $options)
    {
        echo $message->getBody() . PHP_EOL;
        throw new \Exception("Error Processing Request", 1);
        //return true;
    }
    public function setDic($dic)
    {
        $this->_dic = $dic;
    }
}
