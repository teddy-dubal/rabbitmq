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
$ck       = isset($argv[1]) ? $argv[1] : 'local';
$av       = new App\Rabbitmq\RabbitMQ($c);
$consumer = $av->setDebug()->getConsumer('email_send', $ck);
$consumer->consume();
