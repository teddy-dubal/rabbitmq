<?php

use Monolog\Logger;

if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require __DIR__ . '/../../../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../../vendor/autoload.php';
}

$c                           = new \Pimple\Container();
$c['log']                    = new Logger('rpc_server');
$options['reconnect_period'] = 3;
$ck                          = isset($argv[1]) ? $argv[1] : 'local';
while (true) {
    try {
        $av = new App\Rabbitmq\RabbitMQ($c);
        $av->setDebug(true)->getRpcServer($ck, $ck);
        break;
    } catch (Exception $e) {
        error_log($e);
        //var_dump($e);
        sleep($options['reconnect_period']);
    }
}
