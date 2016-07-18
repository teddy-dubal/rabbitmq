<?php

/**
 * The MIT License
 *
 * Copyright (c) 2010 Alvaro Videla
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 *
 * @category   Thumper
 * @package    Thumper
 */

namespace Weez\Rabbitmq\Mod;

use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 *
 *
 *
 * @category   Thumper
 * @package    Thumper
 */
class Producer extends \Thumper\Producer {

    private $_dic;

    public function __construct($con_params) {
        $conn = new AMQPLazyConnection($con_params['host'], $con_params['port'], $con_params['user'], $con_params['password'], $con_params['vhost']);
        parent::__construct($conn);
    }

    public function setDic($dic) {
        $this->_dic = $dic;
    }

    public function publish($msgBody, $routingKey = '', $msg_arguments = array())
    {
        if (!$this->exchangeReady) {
            //declare a durable non autodelete exchange
            $this->ch->exchange_declare($this->exchangeOptions['name'], $this->exchangeOptions['type'], $this->exchangeOptions['passive'], $this->exchangeOptions['durable'], $this->exchangeOptions['auto_delete'], $this->exchangeOptions['internal'], $this->exchangeOptions['nowait'], $this->exchangeOptions['arguments'], $this->exchangeOptions['ticket']);
            $this->exchangeReady = true;
        }

        $msg = new AMQPMessage($msgBody, array_merge(array('content_type' => 'text/plain', 'delivery_mode' => 2), $msg_arguments));
        $this->ch->basic_publish($msg, $this->exchangeOptions['name'], $routingKey);
    }

}
