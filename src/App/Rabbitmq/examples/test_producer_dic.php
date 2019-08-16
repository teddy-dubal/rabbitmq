<?php

if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require __DIR__ . '/../../../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../../vendor/autoload.php';
}
# exemple 1 : topic de base
$p = [

];
$c = new Pimple\Container();
// $c['rabbitmq_conf'] = $p;
$ck       = isset($argv[1]) ? $argv[1] : 'email_send';
$producer = new App\Rabbitmq\RabbitMQ($c);
//$rt_k               = $routing_keys[4];
for ($i = 0; $i < 100; $i++) {
    $rt_k = '';
    $msg  = json_encode(['blabla' => 'FTW ' . $i]);
    $producer->setDebug(true)->publish($ck, $msg, $rt_k, [], $ck);
    echo " [x] Sent ", $msg, "\n";
}
