<?php

$base_config = [
    'connections' => [
        'default'   => [
            'host'     => 'rabbitmq',
            'port'     => 5672,
            'user'     => 'lae',
            'password' => 'lae',
            'vhost'    => '/prod',
        ],
        'test'      => [
            'host'     => 'rabbitmq',
            'port'     => 5672,
            'user'     => 'lae',
            'password' => 'lae',
            'vhost'    => '/dev',
        ],
        'local'     => [
            'host'     => 'rabbitmq',
            'port'     => 5672,
            'user'     => 'lae',
            'password' => 'lae',
            'vhost'    => '/dev',
        ],
        'notify_ws' => [
            'host'     => 'lae_rabbitmq',
            'port'     => 5672,
            'user'     => 'lae',
            'password' => 'lae',
            'vhost'    => '/notify_ws',
        ],
    ],
    'producers'   => [
        'default'   => [
            'exchange' => 'default_topic',
        ],
        'local'     => [
            'exchange' => 'default_topic',
        ],
        'notify_ws' => [
            'exchange' => 'notify_ws',
        ],
    ],
    'consumers'   => [
        'email_send'                 => [
            'exchange' => 'default_topic',
            'queues'   => [
                'email_send',
            ],
        ],
        'test'                       => [
            'exchange' => 'default_topic',
            'queues'   => [
                'catch_all',
            ],
        ],
        'local'                      => [
            'exchange' => 'default_topic',
            'queues'   => [
                'catch_all',
            ],
        ],
        'base_delayed_do_not_launch' => [
            'exchange' => 'default_topic',
            'queues'   => [
                'delayed',
            ],
        ],
        'dead_letter'                => [
            'exchange' => 'dead_topic',
            'queues'   => [
                'dead_letter',
            ],
        ],
    ],
    'exchanges'   => [
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
        'notify_ws'      => [
            'exchange_options' => [
                'name'        => 'amq.topic',
                'type'        => 'topic',
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
                'internal'    => false,
                'nowait'      => false,
            ],
        ],
    ],
    'queues'      => [
        'email_send'  => [
            'options'     => [
                'name' => 'App.Q.Topic.v1.email_send',
            ],
            'routing_key' => 'email.send',
            'callback'    => 'App\Rabbitmq\Workers\emailWorker',
        ],
        'catch_all'   => [
            'options'     => [
                'name' => 'App.Q.Topic.v1.catch_all',
            ],
            'routing_key' => '#',
            'callback'    => 'App\Rabbitmq\Workers\debugWorker',
        ],
        'dead_letter' => [
            'options'     => [
                'name' => 'App.Q.Topic.v1.dead_letter',
            ],
            'routing_key' => '#',
            'callback'    => 'App\Rabbitmq\Workers\deadLetterWorker',
        ],
        'delayed'     => [
            'options'     => [
                'name'      => 'App.Q.Topic.v1.delayed',
                'arguments' => [
                    'x-dead-letter-exchange' => [
                        'S',
                        'App.E.Topic.v0.Dead',
                    ],
                ],
            ],
            'routing_key' => 'delayed.#',
            'callback'    => 'App\Rabbitmq\Workers\debugWorker',
        ],
    ],
    'rpc_servers' => [
        'default' => [
            'exchange' => 'default_direct',
            'callback' => 'App\Rabbitmq\Workers\rpcCallback',
        ],
        'test'    => [
            'exchange' => 'default_direct',
            'callback' => 'App\Rabbitmq\Workers\debugRpcCallback',
        ],
        'local'   => [
            'exchange' => 'default_direct',
            'callback' => 'App\Rabbitmq\Workers\debugRpcCallback',
        ],
    ],
    'rpc_clients' => [
        'default' => [
            'exchange' => 'default_direct',
        ],
        'test'    => [
            'exchange' => 'default_direct',
        ],
        'local'   => [
            'exchange' => 'default_direct',
        ],
    ],
];
