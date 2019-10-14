<?php

if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require __DIR__ . '/../../../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../../vendor/autoload.php';
}
$p = [
];
$c = new Pimple\Container();
// $c['rabbitmq_conf'] = $p;
$t        = isset($argv[1]) ? $argv[1] : 'local';
$ck       = isset($argv[2]) ? $argv[2] : 'local';
$av       = new App\Rabbitmq\RabbitMQ($c);
$consumer = $av->setDebug()->getConsumer($t, $ck);
$consumer->consume();
