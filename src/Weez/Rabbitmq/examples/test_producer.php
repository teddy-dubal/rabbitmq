<?php

require __DIR__ . '/../../../vendor/autoload.php';

# exemple 1 : topic de base

$routing_keys = array(
    'user.logged',
    'user.loggedout',
    'user.registered',
    'user.password.forgot',
    'donation.toto',
    'donation.success',
    'delayed.stream.started'
);

$p        = array(
    'connections' => array(
        'local' => array(
            'lazy'     => true,
            'host'     => '172.17.0.2',
            'port'     => 5672,
            'user'     => 'guest',
            'password' => 'guest',
            'vhost'    => '/'
        )
    ),
    'producers'   => array(
        'local' => array(
            'exchange' => 'default_direct'
        )
    ),
//    'consumers'   => array(
//        'local' => array(
//            'exchange' => 'default_direct',
//            'queues'   => array(
//                'catch_all'
//            )
//        )
//    ),
    'exchanges'   => array(
        'default_topic'  => array(
            'exchange_options' => array(
                'name'        => 'Weez.E.Topic.v0.Default',
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
                'name'        => 'Weez.E.direct.v0.default',
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
                'name'        => 'Weez.E.Topic.v0.Dead',
                'type'        => 'topic',
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
                'internal'    => false,
                'nowait'      => false,
            )
        ),
    ),
//    'queues'      => array(
//        'catch_all' => array(
//            'options'     => array(
//                'name' => 'Weez.Q.Topic.v1.catch_all',
//            ),
////            'exchange'    => 'default_topic',
//            'routing_key' => '#',
//            'callback'    => 'Weez\Rabbitmq\Workers\debugWorker'
//        ),
//    ),
);
$c                  = new Pimple\Container();
$c['rabbitmq_conf'] = $p;
$ck       = isset($argv[1]) ? $argv[1] : 'local';
$producer           = new Weez\Rabbitmq\RabbitMQ($c);
for ($i = 0; $i < 10; $i++) {
    $rt_k = $routing_keys[array_rand($routing_keys)];
    var_dump($rt_k);
    $msg = json_encode(array('blabl' => 'FTW ' . $i));
    $producer->publish($ck, $msg, $rt_k, array(), $ck);
}
