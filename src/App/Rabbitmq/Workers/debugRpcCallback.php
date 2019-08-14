<?php

namespace App\Rabbitmq\Workers;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class debugRpcCallback implements ProcessorInterface {

    public function process(Message $message, array $options)
    {
        // var_dump($options);
        echo $message->getBody() . PHP_EOL;
        return true;
    }
}
