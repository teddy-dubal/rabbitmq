<?php

namespace App\Rabbitmq\Workers;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class RpcCallback implements ProcessorInterface
{

    private $_dic;

    public function process(Message $message, array $options)
    {
        $body   = $message->getBody();
        $dic    = $this->_dic;
        $result = json_encode(['status' => 0, 'result' => ['error' => 'unknow_callback']]);
        $action = explode('::', $body['client.call_action']);
        $this->_dic['log']->debug($body['client.call_action'], $body);
        if (class_exists($action[0]) && method_exists($action[0], $action[1])) {
            $time_start = microtime(true);
            $object     = new $action[0]($this->_dic);
            $result     = $object->{$action[1]}($body);
            $time       = microtime(true) - $time_start;
            $this->_dic['log']->debug(sprintf('[RCP-CALLBACK] action %s in %s => %s', $body['client.call_action'], strval($time), json_decode($result, true)));
        }
        return $result;
    }

    public function setDic($dic)
    {
        $this->_dic = $dic;
    }

}
