<?php

namespace Weez\Rabbitmq;

use \Exception;
use \Weez\Rabbitmq\Mod\Consumer;
use \Weez\Rabbitmq\Mod\Producer;
use \Weez\Rabbitmq\Mod\RpcClient;
use \Weez\Rabbitmq\Mod\RpcServer;
use \Pimple\Container;
use \Thumper\AnonConsumer;

/**
 * Weez helper class to use RabbitMQ
 */
class RabbitMQ {

    protected $c; // Pimple
    protected $config;
    protected $is_debug = false;

    public function __construct(Container $c) {
        $this->c = $c;

        $this->initConfig();
    }

    protected function initConfig() {
        if (file_exists(dirname(__FILE__) . '/config/config.inc.php')) {
            $this->config = include_once(dirname(__FILE__) . '/config/config.inc.php');
            if (isset($this->c['rabbitmq_conf'])) {
                $this->config = array_replace_recursive($this->config, $this->c['rabbitmq_conf']);
            }
        }
        return $this->config;
    }

    protected function getConfig($key, $default = null) {
        return (!empty($this->config[$key])) ? $this->config[$key] : $default;
    }
    /**
     *
     * @param boolean $debug
     * @return \Weez\Rabbitmq\RabbitMQ
     */
    public function setDebug($debug)
    {
        $this->is_debug = $debug;
        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function isDebug()
    {
        return (boolean) $this->is_debug;
    }

    /**
     *
     * @global type $is_debug
     * @param type $producer
     * @param type $msg
     * @param type $routing_key
     * @param type $msg_arguments
     * @param type $connection
     */
    public function publish($producer, $msg, $routing_key = '', $msg_arguments = array(), $connection = 'default') {
        if (!$this->is_debug) {
            try {
                //if (!isset($producers[$producer])) {
                $producers[$producer] = $this->getProducer($producer, $connection);
                //}
                if (is_array($msg_arguments) && count($msg_arguments)) {
                    if (isset($msg_arguments['durable'])) {
                        // this is in seconds, convert it to rabbitmq values (milliseconds)
                        $msg_arguments['delivery_mode'] = $msg_arguments['durable'] ? 2 : 1;
                        unset($msg_arguments['durable']);
                    }
                    if (isset($msg_arguments['ttl'])) {
                        // this is in seconds, convert it to rabbitmq values (milliseconds)
                        $msg_arguments['expiration'] = $msg_arguments['ttl'] * 1000;
                        unset($msg_arguments['ttl']);
                    }
                }
                $producers[$producer]->publish(json_encode($msg), $routing_key, $msg_arguments);
            } catch (Exception $e) {
                if ($this->c->offsetExists('log'))
                    $this->c['log']->addWarning('Warning error in publish: ' . $e->getMessage());
            }
        } else {
            $this->synchronousPublish($msg, $routing_key);
        }
    }

    public function publishDelayed($producer, $msg, $routing_key = '', $ttl = 60, $msg_arguments = array()) {
        static $producers;
        global $is_debug;

        if (!$is_debug) {
            try {
                if (!isset($producers[$producer])) {
                    $producers[$producer] = $this->getProducer($producer);
                }
                $msg_arguments['ttl'] = $ttl;

                if (is_array($msg_arguments) && count($msg_arguments)) {
                    if (isset($msg_arguments['durable'])) {
                        // this is in seconds, convert it to rabbitmq values (milliseconds)
                        $msg_arguments['delivery_mode'] = $msg_arguments['durable'] ? 2 : 1;
                        unset($msg_arguments['durable']);
                    }
                    if (isset($msg_arguments['ttl'])) {
                        // this is in seconds, convert it to rabbitmq values (milliseconds)
                        $msg_arguments['expiration'] = $msg_arguments['ttl'] * 1000;
                        unset($msg_arguments['ttl']);
                    }
                }

                $routing_key = 'delayed.' . $routing_key;

                $producers[$producer]->publish(json_encode($msg), $routing_key, $msg_arguments);
            } catch (Exception $e) {
                if ($this->c->offsetExists('log'))
                    $this->c['log']->addWarning('Warning error in publish: ' . $e->getMessage());
            }
        } else {
            $this->synchronousPublish($msg, $routing_key);
        }
    }

    public function synchronousPublish($msg, $routing_key = '') {
        $queues = $this->getConfig('queues');
        foreach ($queues as $queue) {
            if (isset($queue['routing_key'])) {
                if ($queue['routing_key'] === '#' || ($queue['routing_key'] === $routing_key)) {
                    try {
                        call_user_func(array($queue['callback'], 'execute'), $msg, null);
                    } catch (Exception $e) {
                        if ($this->c->offsetExists('log'))
                            $this->c['log']->addWarning('Warning error in publish: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    protected function getConnectionParams($connection = 'default') {
        static $config;

        if (!$config) {
            $config = $this->getConfig('connections');
            if (!$config) {
                throw new Exception(sprintf('There is no rabbitmq connection in config'));
            }

            // a array has been passed in parameter, merge it with default values
            if (is_array($connection)) {
                $config = $config['default'];
                $config = array_merge($config, $connection);
            }
            // a string has been passed in parameter
            else {
                $connection = (isset($connection)) ? $connection : 'default';
                $config     = $config[$connection];
            }

            if (!$config) {
                throw new Exception(sprintf('There is no rabbitmq connection with "%s" name in config', $connection));
            }
        }

        if (empty($config['host'])) {
            throw new Exception(sprintf('%s rabbitmq connection must have configured host', $connection));
        }
        if (empty($config['user'])) {
            throw new Exception(sprintf('%s rabbitmq connection must have configured user', $connection));
        }
        if (!isset($config['password'])) {
            throw new Exception(sprintf('%s rabbitmq connection must have configured password', $connection));
        }
        if (!isset($config['vhost'])) {
            throw new Exception(sprintf('%s rabbitmq connection must have configured vhost', $connection));
        }

        return array(
            'host'     => $config['host'],
            'port'     => empty($config['port']) ? 5672 : $config['port'],
            'user'     => $config['user'],
            'password' => $config['password'],
            'vhost'    => $config['vhost']
        );
    }

    public function getProducer($name, $connection = 'default') {
        $config = $this->getConfig('producers');
        if (empty($config[$name]) or ! $config = $config[$name]) {
            throw new Exception(sprintf('There is no rabbitmq producer with "%s" name in config', $name));
        }
        $con_params = $this->getConnectionParams($connection);
        $producer   = new Producer($con_params);
        $producer->setDic($this->c);
        $this->setExchange($producer, $config);
        return $producer;
    }

    public function getConsumer($name, $connection = 'default') {
        $config = $this->getConfig('consumers');

        if (empty($config[$name]) or ! $config = $config[$name]) {
            throw new Exception(sprintf('There is no rabbitmq consumers with "%s" name in config', $name));
        }

        $con_params = $this->getConnectionParams($connection);

        $consumer = new Consumer($con_params);
        $consumer->setDic($this->c);
        $this->setExchange($consumer, $config);
        echo "Connected to " . $con_params['host'] . ":" . $con_params['port'] . " (vhost:" . $con_params['vhost'] . ")\n";
        // get queues
        $queues = array();
        if (!empty($config['queues'])) {
            $queue_config = $this->getConfig('queues');
            if (is_array($config['queues'])) {
                foreach ($config['queues'] as $queue) {
                    echo "queue: " . $queue . "\n";
                    self::_processQueues($consumer, $queue_config[$queue]);
                }
            } else {
                echo "queue: " . $config['queues'] . "\n";
                self::_processQueues($consumer, $queue_config[$config['queues']]);
            }
        } else {
            self::_processQueues($consumer, $config);
        }

        return $consumer;
    }

    protected static function _processQueues($consumer, $config) {

        $queue_options = empty($config['options']) ? array() : $config['options'];
        $consumer->setQueueOptions($queue_options);

        if (!empty($config['callback'])) {
            $consumer->setCallback(array($config['callback'], 'execute'));
        }
        if (!empty($config['routing_key'])) {
            if (is_array($config['routing_key'])) {
                foreach ($config['routing_key'] as $routing_key) {
                    echo "-> routing key: " . $routing_key . "\n";
                    $consumer->setRoutingKey($routing_key);
                }
            } else {
                echo "-> routing key: " . $config['routing_key'] . "\n";
                $consumer->setRoutingKey($config['routing_key']);
            }
        }
    }

    protected function setExchange($amqp_client, $config) {
        $exchange_name   = empty($config['exchange']) ? 'default' : $config['exchange'];
        $exchange_config = $this->getConfig('exchanges');
        $exchange_config = empty($exchange_config[$exchange_name]) ? array() : $exchange_config[$exchange_name];

        $exchange_options = empty($exchange_config['exchange_options']) ? array() : $exchange_config['exchange_options'];
        $amqp_client->setExchangeOptions($exchange_options);
    }

    public function getAnonConsumer($name, $connection = null) {
        $config = $this->getConfig('anon_consumers');

        if (empty($config[$name]) or ! $config = $config[$name]) {
            throw new Exception(sprintf('There is no rabbitmq anon consumers with "%s" name in config', $name));
        }

        $con_params = $this->getConnectionParams($connection);

        $consumer = new AnonConsumer($con_params['host'], $con_params['port'], $con_params['user'], $con_params['password'], $con_params['vhost']);

        $this->setExchange($consumer, $config);

        $queue_config['options']     = array('name'        => '', 'passive'     => false, 'durable'     => false,
            'exclusive'   => true, 'auto_delete' => true, 'nowait'      => false,
            'arguments'   => null, 'ticket'      => null);
        $queue_config['routing_key'] = $config['routing_key'];
        $queue_config['callback']    = $config['callback'];

        self::_processQueues($consumer, $queue_config);

        return $consumer;
    }

    public function getRpcClient($name, $connection = 'default') {
        $config = $this->getConfig('rpc_clients');

        if (empty($config[$name]) or ! $config = $config[$name]) {
            throw new Exception(sprintf('There is no rabbitmq rpc client with "%s" name in config', $name));
        }

        $con_params = $this->getConnectionParams($connection);

        $client = new RpcClient($con_params);

        $this->setExchange($client, $config);
        $client->initClient();

        return $client;
    }

    public function getRpcServer($name, $connection = 'default') {
        $config = $this->getConfig('rpc_servers');
        if (empty($config[$name]) or ! $config = $config[$name]) {
            throw new Exception(sprintf('There is no rabbitmq rpc server with "%s" name in config', $name));
        }
        if (empty($config['callback'])) {
            throw new Exception(sprintf('Callback must be set for rabbitmq rpc server with "%s" name', $name));
        }

        $con_params = $this->getConnectionParams($connection);
        echo "Connected to " . $con_params['host'] . ":" . $con_params['port'] . " (vhost:" . $con_params['vhost'] . ")\n";
        echo "Connection name : " . $connection . " - Server name : " . $name . "\n";

        $server = new RpcServer($con_params);

        $this->setExchange($server, $config);
        $server->setDic($this->c);
        $server->initServer($name);
        $server->setCallback(array($config['callback'], 'execute'));
        $server->start();

        return $server;
    }

}
