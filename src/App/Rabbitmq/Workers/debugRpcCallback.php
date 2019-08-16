<?php

namespace App\Rabbitmq\Workers;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class debugRpcCallback implements ProcessorInterface
{

    public function process(Message $message, array $options)
    {
        return '[' . date('Y-m-d H:i:s') . '] ' . $message->getBody();
    }
}
