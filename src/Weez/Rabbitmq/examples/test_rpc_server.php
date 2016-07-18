<?php

require __DIR__ . '/../../../vendor/autoload.php';

global $is_debug;
$is_debug                    = true;
$c                           = new Pimple\Container();
$options['reconnect_period'] = 3;
$ck                          = isset($argv[1]) ? $argv[1] : 'local';
while (true) {
    try {
        $av = new Weez\Rabbitmq\RabbitMQ($c);
        $av->getRpcServer($ck, $ck);
        break;
    } catch (Exception $e) {
        error_log($e);
        var_dump($e);
        sleep($options['reconnect_period']);
    }
}
