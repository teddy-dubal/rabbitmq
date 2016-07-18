<?php

namespace Weez\Rabbitmq\Workers;

class debugWorker
{
  public static function execute($body, $delivery_info)
  {
    var_dump($delivery_info['routing_key'] . ' ' .$body);
  }
}
