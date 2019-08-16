<?php

namespace App\Rabbitmq\Workers;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;

class rpcCallback implements ProcessorInterface
{

    private $_dic;

    public function process(Message $message, array $options)
    {
        $body   = $message->getBody();
        $dic    = $this->_dic;
        $result = json_encode(['status' => 0, 'result' => ['error' => 'unknow_callback']]);
        $action = explode('::', $body['client.call_action']);
        $dic['log']->addNotice($body['client.call_action'] . json_encode($body));
        if (class_exists($action[0]) && method_exists($action[0], $action[1])) {
            $time_start = microtime(true);
            $object     = new $action[0]($dic);
            $result     = $object->{$action[1]}($body);
            $time       = microtime(true) - $time_start;
            $dic['log']->addNotice(sprintf('action %s in %s => %s', $body['client.call_action'], strval($time), $result));
            if ($dic['debug']) {
                $dt = date('Y-m-d H:i:s');
                echo '*-------------' . $dt . '---------------* ' . PHP_EOL;
                echo $body['client.call_action'] . PHP_EOL;
                var_dump($body);
                echo '**** ' . PHP_EOL;
                var_dump(json_decode($result, true));
                echo '*-------------' . $dt . '---------------* ' . PHP_EOL;
            }
        }
        return $result;
    }

    public function setDic($dic)
    {
        $this->_dic = $dic;
    }

}
