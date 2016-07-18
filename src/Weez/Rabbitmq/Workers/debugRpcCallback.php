<?php

namespace Weez\Rabbitmq\Workers;

class debugRpcCallback {

    public static function execute($body, $delivery_info = '') {
        return json_encode(array($delivery_info, $body));
    }

}
