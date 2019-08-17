<?php

namespace App\Rabbitmq;

use \App\Rabbitmq\Mod\Consumer;
use \App\Rabbitmq\Mod\Producer;
use \App\Rabbitmq\Mod\RpcClient;
use \App\Rabbitmq\Mod\RpcServer;
use \Exception;
use \Pimple\Container;
use Monolog\Logger;

/**
 * App helper class to use RabbitMQ
 */
class RabbitMQ
{
    /**
     * @var Container
     */
    protected $c;
    /**
     * @var array
     */
    protected $config;
    /**
     * @var boolean
     */
    protected $is_debug = false;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * Undocumented function
     *
     * @param \Pimple\Container $c
     */
    public function __construct(Container $c, $logger = null)
    {
        $this->logger = $logger ?? new Logger('rabbitmq');
        $this->c      = $c;
        $this->initConfig();
    }
    /**
     * Undocumented function
     *
     * @return array
     */
    protected function initConfig()
    {
        if (file_exists(dirname(__FILE__) . '/config/config.inc.php')) {
            $this->config = include dirname(__FILE__) . '/config/config.inc.php';
            if (isset($this->c['rabbitmq_conf'])) {
                $this->config = array_replace_recursive($this->config, $this->c['rabbitmq_conf']);
            }
        }
        return $this->config;
    }

    /**
     * Undocumented function
     *
     * @param string $key
     * @param string $default
     *
     * @return array|null
     */
    protected function getConfig($key, $default = null)
    {
        return (!empty($this->config[$key])) ? $this->config[$key] : $default;
    }

    /**
     *
     * @param boolean $debug
     * @return self
     */
    public function setDebug($debug = true)
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
     * @param Producer $producer
     * @param string $msg
     * @param string $routing_key
     * @param array $msg_arguments
     * @param string $connection
     */
    public function publish($producer, $msg, $routing_key = '', $msg_arguments = [], $connection = 'default')
    {
        try {
            //if (!isset($producers[$producer])) {
            $producers[$producer] = $this->getProducer($producer, $connection);
            //}
            if (is_array($msg_arguments) && count($msg_arguments)) {
                if (isset($msg_arguments['durable'])) {
                    // this is in seconds, convert it to rabbitmq values (milliseconds)
                    $msg_arguments['delivery_mode'] = $msg_arguments['durable'] ? AMQP_DURABLE : AMQP_JUST_CONSUME;
                    unset($msg_arguments['durable']);
                }
                if (isset($msg_arguments['ttl'])) {
                    // this is in seconds, convert it to rabbitmq values (milliseconds)
                    $msg_arguments['expiration'] = $msg_arguments['ttl'] * 1000;
                    unset($msg_arguments['ttl']);
                }
            }
            $producers[$producer]->publish($msg, $routing_key, $msg_arguments);
        } catch (Exception $e) {
            $this->logger and $this->logger->error('[publish] error :' . $e->getMessage());
        }

    }
    /**
     * Undocumented function
     *
     * @param Producer $producer
     * @param string $msg
     * @param string $routing_key
     * @param array $msg_arguments
     * @param string $connection
     *
     * @return void
     */
    public function publishWebSocket($producer, $msg, $routing_key = '', $msg_arguments = [], $connection = 'default')
    {
        try {
            $producers[$producer] = $this->getProducer($producer, $connection);
            $producers[$producer]->setExchangeReady(true);
            $producers[$producer]->publish(json_encode($msg), $routing_key, $msg_arguments);
        } catch (Exception $e) {
            $this->logger and $this->logger->error('[publishWebSocket] error :' . $e->getMessage());
        }
    }

    public function publishDelayed($producer, $msg, $routing_key = '', $ttl = 60, $msg_arguments = [])
    {
        static $producers;
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
            $this->logger and $this->logger->error('[publishDelayed] error :' . $e->getMessage());

        }
    }

    /**
     * Undocumented function
     *
     * @param string $connection
     *
     * @return void
     */
    protected function getConnectionParams($connection = 'default')
    {
        static $conf;
        $config = $conf[$connection] ?? false;

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
                $config     = $conf[$connection]     = $config[$connection];
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

        return [
            'host'     => $config['host'],
            'port'     => empty($config['port']) ? 5672 : $config['port'],
            'user'     => $config['user'],
            'password' => $config['password'],
            'vhost'    => $config['vhost'],
        ];
    }
    /**
     * Undocumented function
     *
     * @param string $name
     * @param string $connection
     *
     * @return Producer
     */
    public function getProducer($name, $connection = 'default')
    {
        $config = $this->getConfig('producers');
        if (empty($config[$name]) or !$config = $config[$name]) {
            throw new Exception(sprintf('There is no rabbitmq producer with "%s" name in config', $name));
        }
        $con_params = $this->getConnectionParams($connection);
        $producer   = new Producer($con_params);
        $producer->setDic($this->c);
        $this->setExchange($producer, $config);
        return $producer;
    }
    /**
     * Undocumented function
     *
     * @param string $name
     * @param string $connection
     *
     * @return Consumer
     */
    public function getConsumer($name, $connection = 'default')
    {
        $config = $this->getConfig('consumers');

        if (empty($config[$name]) or !$config = $config[$name]) {
            throw new Exception(sprintf('There is no rabbitmq consumers with "%s" name in config', $name));
        }

        $con_params = $this->getConnectionParams($connection);

        $consumer = new Consumer($con_params);
        $consumer->setDic($this->c);
        $this->setExchange($consumer, $config);
        $this->logger and $this->logger->info("[Consumer] Connected to " . $con_params['host'] . ":" . $con_params['port'] . " (vhost:" . $con_params['vhost'] . ")\n");
        $this->logger and $this->logger->info("[Consumer] Connection name : " . $connection . " - Server name : " . $name . "\n");
        // get queues
        $queues = [];
        if (!empty($config['queues'])) {
            $queue_config = $this->getConfig('queues');
            if (is_array($config['queues'])) {
                foreach ($config['queues'] as $queue) {
                    $this->logger and $this->logger->info("queue: " . $queue . "\n");
                    self::_processQueues($consumer, $queue_config[$queue]);
                }
            } else {
                $this->logger and $this->logger->info("queue: " . $config['queues'] . "\n");
                self::_processQueues($consumer, $queue_config[$config['queues']]);
            }
        } else {
            self::_processQueues($consumer, $config);
        }

        return $consumer;
    }

    protected static function _processQueues($consumer, $config)
    {

        $queue_options = empty($config['options']) ? [] : $config['options'];
        $consumer->setQueueOptions($queue_options);

        if (!empty($config['callback'])) {
            $consumer->setCallback($config['callback']);
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
    /**
     * Undocumented function
     *
     * @param Producer|Consumer|RpcClient|RpcServer $amqp_client
     * @param array $config
     *
     * @return self
     */
    protected function setExchange($amqp_client, $config)
    {
        $exchange_name    = empty($config['exchange']) ? 'default' : $config['exchange'];
        $exchange_config  = $this->getConfig('exchanges');
        $exchange_config  = empty($exchange_config[$exchange_name]) ? [] : $exchange_config[$exchange_name];
        $exchange_options = empty($exchange_config['exchange_options']) ? [] : $exchange_config['exchange_options'];
        $amqp_client->setExchangeOptions($exchange_options);
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @param string $connection
     *
     * @return RpcClient
     */
    public function getRpcClient($name, $connection = 'default')
    {
        $config = $this->getConfig('rpc_clients');

        if (empty($config[$name]) or !$config = $config[$name]) {
            throw new Exception(sprintf('There is no rabbitmq rpc client with "%s" name in config', $name));
        }

        $con_params = $this->getConnectionParams($connection);

        $client = new RpcClient($con_params);
        $this->setExchange($client, $config);
        $client->initClient($name);

        return $client;
    }
    /**
     * Undocumented function
     *
     * @param string $name
     * @param string $connection
     *
     * @return RpcServer
     */
    public function getRpcServer($name, $connection = 'default')
    {
        $config = $this->getConfig('rpc_servers');
        if (empty($config[$name]) or !$config = $config[$name]) {
            throw new Exception(sprintf('There is no rabbitmq rpc server with "%s" name in config', $name));
        }
        if (empty($config['callback'])) {
            throw new Exception(sprintf('Callback must be set for rabbitmq rpc server with "%s" name', $name));
        }

        $con_params = $this->getConnectionParams($connection);
        $this->logger and $this->logger->info("[Rpc-Server] Connected to " . $con_params['host'] . ":" . $con_params['port'] . " (vhost:" . $con_params['vhost'] . ")\n");
        $this->logger and $this->logger->info("[Rpc-Server] Connection name : " . $connection . " - Server name : " . $name . "\n");

        $server = new RpcServer($con_params);

        $this->setExchange($server, $config);
        $server->setDic($this->c);
        $server->initServer($name);
        $server->setCallback($config['callback']);
        $server->start();

        return $server;
    }

}
