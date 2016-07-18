<?php

$base_config = array(
    'connections' => array(
        'default' => array(
            'lazy'     => true,
            'host'     => '172.17.0.2',
            'port'     => 5672,
            'user'     => 'guest',
            'password' => 'guest',
            'vhost'    => '/prod'
        ),
        'test'    => array(
            'lazy'     => true,
            'host'     => '172.17.0.2',
            'port'     => 5672,
            'user'     => 'guest',
            'password' => 'guest',
            'vhost'    => '/dev'
        ),
        'local'   => array(
            'lazy'     => true,
            'host'     => '172.17.0.2',
            'port'     => 5672,
            'user'     => 'guest',
            'password' => 'guest',
            'vhost'    => '/'
        )
    ),
    'producers'   => array(
        'default' => array(
            'exchange' => 'default_topic'
        ),
        'local'   => array(
            'exchange' => 'default_topic'
        )
    ),
    'consumers'   => array(
        'notify'                     => array(
            'exchange' => 'default_topic',
            'queues'   => array(
                'email_send'
            )
        ),
        'test'                       => array(
            'exchange' => 'default_topic',
            'queues'   => array(
                'catch_all'
            )
        ),
        'local'                      => array(
            'exchange' => 'default_topic',
            'queues'   => array(
                'catch_all'
            )
        ),
        'base_delayed_do_not_launch' => array(
            'exchange' => 'default_topic',
            'queues'   => array(
                'delayed'
            )
        ),
        'dead_letter'                => array(
            'exchange' => 'dead_topic',
            'queues'   => array(
                'dead_letter'
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
        'email_send'  => array(
            'options'     => array(
                'name' => 'Weez.Q.Topic.v1.email_send',
            ),
            'routing_key' => 'email.send',
            'callback'    => 'Weez\Rabbitmq\Workers\emailWorker'
        ),
        'catch_all'   => array(
            'options'     => array(
                'name' => 'Weez.Q.Topic.v1.catch_all',
            ),
            'routing_key' => '#',
            'callback'    => 'Weez\Rabbitmq\Workers\debugWorker'
        ),
        'dead_letter' => array(
            'options'     => array(
                'name' => 'Weez.Q.Topic.v1.dead_letter',
            ),
            'routing_key' => '#',
            'callback'    => 'Weez\Rabbitmq\Workers\deadLetterWorker'
        ),
        'delayed'     => array(
            'options'     => array(
                'name'      => 'Weez.Q.Topic.v1.delayed',
                'arguments' => array(
                    'x-dead-letter-exchange' => array(
                        'S',
                        'Weez.E.Topic.v0.Dead'
                    )
                )
            ),
            'routing_key' => 'delayed.#',
            'callback'    => 'Weez\Rabbitmq\Workers\debugWorker'
        )
    ),
    'rpc_servers' => array(
        'default' => array(
            'exchange' => 'default_direct',
            'callback' => 'Weez\Rabbitmq\Workers\rpcCallback'
        ),
        'test'    => array(
            'exchange' => 'default_direct',
            'callback' => 'Weez\Rabbitmq\Workers\debugRpcCallback'
        ),
        'local'   => array(
            'exchange' => 'default_direct',
            'callback' => 'Weez\Rabbitmq\Workers\debugRpcCallback'
        )
    ),
    'rpc_clients' => array(
        'default' => array(
            'exchange' => 'default_direct',
        ),
        'test'    => array(
            'exchange' => 'default_direct',
        ),
        'local'   => array(
            'exchange' => 'default_direct',
        )
    ),
);
