<?php

if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require __DIR__ . '/../../../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../../vendor/autoload.php';
}
$options['reconnect_period'] = 3;
$c                           = new Pimple\Container();
try {
    $av     = new App\Rabbitmq\RabbitMQ($c);
    $ck     = isset($argv[1]) ? $argv[1] : 'local';
    $client = $av->getRpcClient($ck, $ck);
    $client->addRequest('test1'); //the third parameter is the request identifie
    echo "Waiting for replies…\n";
    $replies = $client->getReplies();
    var_dump('REPLY : ',$replies);
} catch (Exception $e) {
    error_log($e);
    //var_dump($e);
    sleep($options['reconnect_period']);
}
