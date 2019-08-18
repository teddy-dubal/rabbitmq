<?php

if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require __DIR__ . '/../../../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../../vendor/autoload.php';
}
# exemple 1 : topic de base

$routing_keys = [
    'user.logged',
    'user.loggedout',
    'user.registered',
    'user.password.forgot',
    'donation.toto',
    'donation.success',
    'delayed.stream.started',
];

$p = [
    'producers' => [
        'local' => [
            'exchange' => 'default_direct',
        ],
    ],
//    'consumers'   => array(
    //        'local' => array(
    //            'exchange' => 'default_direct',
    //            'queues'   => array(
    //                'catch_all'
    //            )
    //        )
    //    ),
    'exchanges' => [
        'default_topic'  => [
            'exchange_options' => [
                'name'        => 'App.E.Topic.v0.Default',
                'type'        => 'topic',
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
                'internal'    => false,
                'nowait'      => false,
            ],
        ],
        'default_direct' => [
            'exchange_options' => [
                'name'        => 'App.E.direct.v0.default',
                'type'        => 'direct',
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
                'internal'    => false,
                'nowait'      => false,
            ],
        ],
        'dead_topic'     => [
            'exchange_options' => [
                'name'        => 'App.E.Topic.v0.Dead',
                'type'        => 'topic',
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
                'internal'    => false,
                'nowait'      => false,
            ],
        ],
    ]
];
$c                  = new Pimple\Container();
// $c['rabbitmq_conf'] = $p;
$ck                 = isset($argv[1]) ? $argv[1] : 'local';
$producer           = new App\Rabbitmq\RabbitMQ($c);
//$rt_k               = $routing_keys[4];
for ($i = 0; $i < 100; $i++) {
    $rt_k = $routing_keys[array_rand($routing_keys)];
    $rt_k = '';
    $msg = json_encode(['blabla' => 'FTW ' . $i]);
    $producer->setDebug(true)->publish($ck, $msg, $rt_k, [], $ck);
    echo " [x] Sent ", $msg, "\n";
}
