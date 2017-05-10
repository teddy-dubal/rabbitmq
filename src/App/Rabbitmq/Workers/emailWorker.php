<?php

namespace App\Rabbitmq\Workers;

class emailWorker
{
  public static function execute($body, $delivery_info, $dic)
  {
          
    $object = \App\Modules\BaseController::sendMail($body, $dic);
    }
}

