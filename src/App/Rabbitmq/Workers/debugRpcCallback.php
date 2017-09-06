<?php

namespace App\Rabbitmq\Workers;

class debugRpcCallback {

    public static function execute($body, $delivery_info = '') {
        return json_encode([$delivery_info, $body]);
    }

}
