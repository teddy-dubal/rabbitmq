<?php

require __DIR__ . '/../../../vendor/autoload.php';

global $is_debug;
$is_debug = true;

$c = new Pimple\Container();
try {
    $av      = new Weez\Rabbitmq\RabbitMQ($c);
    $ck      = isset($argv[1]) ? $argv[1] : 'local';
    $client  = $av->getRpcClient($ck, $ck);
    $client->addRequest('test1', $ck, $ck); //the third parameter is the request identifie
    echo "Waiting for replies…\n";
    $replies = $client->getReplies();
    var_dump($replies);
} catch (Exception $e) {
    error_log($e);
    var_dump($e);
    sleep($options['reconnect_period']);
}
