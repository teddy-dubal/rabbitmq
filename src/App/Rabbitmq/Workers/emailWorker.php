<?php

namespace App\Rabbitmq\Workers;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class emailWorker implements ProcessorInterface
{
    private $_dic;

    public function process(Message $message, array $options):bool
    {
        $body   = $message->getBody();
        $object = \App\Modules\BaseController::sendMail($body, $this->_dic);
    }

    public function setDic($dic)
    {
        $this->_dic = $dic;
    }
}
