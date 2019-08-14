<?php

if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require __DIR__ . '/../../../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../../vendor/autoload.php';
}
$p = [
//    'producers'   => array(
    //        'local' => array(
    //            'exchange' => 'default_direct'
    //        )
    //    ),
    'consumers' => [
        'local' => [
            'exchange' => 'default_direct',
            'queues'   => [
                'direct',
                'noty',
            ],
        ],
    ],
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
    ],
    'queues'    => [
        'direct' => [
            'options'     => [
                'name' => 'App.Q.Direct.v1',
            ],
//            'routing_key' => 'donation.toto',
            'routing_key' => '',
            'callback'    => 'App\Rabbitmq\Workers\debugWorker',
        ],
        'noty'   => [
            'options'     => [
                'name' => 'App.Q.Direct.v2',
            ],
//            'routing_key' => 'donation.toto',
            'routing_key' => '',
            'callback'    => 'App\Rabbitmq\Workers\debugWorker',
        ],
    ],
];
$c                  = new Pimple\Container();
$c['rabbitmq_conf'] = $p;
$ck                 = isset($argv[1]) ? $argv[1] : 'local';
$av                 = new App\Rabbitmq\RabbitMQ($c);
$consumer           = $av->setDebug()->getConsumer($ck, $ck);
$consumer->consume();
