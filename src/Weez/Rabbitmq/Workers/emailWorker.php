<?php

namespace Weez\Rabbitmq\Workers;

class emailWorker
{
  public static function execute($body, $delivery_info, $dic)
  {
          
    $object = \Weez\Modules\BaseController::sendMail($body, $dic);
  }
}

