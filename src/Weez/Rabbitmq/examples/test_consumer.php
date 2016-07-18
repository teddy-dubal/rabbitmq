<?php

require __DIR__ . '/../../../vendor/autoload.php';


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
    'consumers'   => array(
        'local' => array(
            'exchange' => 'default_direct',
            'queues'   => array(
                'direct'
            )
        )
    ),
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
    'queues'      => array(
        'direct' => array(
            'options'     => array(
                'name' => 'Weez.Q.Direct.v1',
            ),
            'routing_key' => 'donation.toto',
            'callback'    => 'Weez\Rabbitmq\Workers\debugWorker'
        ),
    ),
);
$c                  = new Pimple\Container();
$c['rabbitmq_conf'] = $p;
$ck       = isset($argv[1]) ? $argv[1] : 'local';
$av       = new Weez\Rabbitmq\RabbitMQ($c);
$consumer = $av->getConsumer($ck, $ck);
$consumer->consume(0);

