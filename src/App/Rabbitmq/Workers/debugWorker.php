<?php

namespace App\Rabbitmq\Workers;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class debugWorker implements ProcessorInterface
{
    private $_dic;

    public function process(Message $message, array $options):bool
    {
        echo $message->getBody() . PHP_EOL;
        return true;
    }
    public function setDic($dic)
    {
        $this->_dic = $dic;
    }
}
