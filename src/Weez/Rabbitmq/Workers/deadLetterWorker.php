<?php

namespace Weez\Rabbitmq\Workers;

class deadLetterWorker {

    public static function execute($body, $delivery_info, $headers = array()) {
        $routing_key = $delivery_info['routing_key'];

        if (strpos($routing_key, 'delayed.') === 0) {
            $original_key = str_replace('delayed.', '', $routing_key);
            // requeue it to the original exhange
            $x_death      = $headers['x-death'][1][0];
            $exchange     = $x_death['exchange'][1];

            echo "dead_letter: republish delayed " . $original_key . "\n";
            RabbitMQ::publish('default', $body, $original_key);

            return true;
        }
    }

}
