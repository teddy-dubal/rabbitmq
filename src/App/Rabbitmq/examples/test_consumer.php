<?php

if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require __DIR__ . '/../../../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../../vendor/autoload.php';
}
$p                  = array(
//    'producers'   => array(
//        'local' => array(
//            'exchange' => 'default_direct'
//        )
//    ),
    'consumers'   => array(
        'local' => array(
//            'exchange' => 'default_direct',
            'queues'   => array(
                'direct',
            )
        )
    ),
    'exchanges'   => array(
        'default_topic'  => array(
            'exchange_options' => array(
                'name' => 'App.E.Topic.v0.Default',
                'type'        => 'topic',
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
                'internal'    => false,
                'nowait'      => false,
            )
        ),
        'default_direct' => array(
            'exchange_options' => array(
                'name' => 'App.E.direct.v0.default',
                'type'        => 'direct',
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
                'internal'    => false,
                'nowait'      => false,
            )
        ),
        'dead_topic'     => array(
            'exchange_options' => array(
                'name' => 'App.E.Topic.v0.Dead',
                'type'        => 'topic',
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
                'internal'    => false,
                'nowait'      => false,
            )
        ),
    ),
    'queues'      => array(
        'direct' => array(
            'options'     => array(
                'name' => 'App.Q.Direct.v1',
            ),
            'routing_key' => 'donation.toto',
            'callback'    => 'App\Rabbitmq\Workers\debugWorker'
        ),
        'noty'   => array(
            'options'     => array(
                'name' => 'App.Q.Direct.v2',
            ),
            'routing_key' => 'donation.toto',
            'callback'    => 'App\Rabbitmq\Workers\debugWorker'
        ),
    ),
);
$c                  = new Pimple\Container();
$c['rabbitmq_conf'] = $p;
$ck       = isset($argv[1]) ? $argv[1] : 'local';
$av       = new App\Rabbitmq\RabbitMQ($c);
$consumer           = $av->setDebug()->getConsumer($ck, $ck);
$consumer->consume(0);

