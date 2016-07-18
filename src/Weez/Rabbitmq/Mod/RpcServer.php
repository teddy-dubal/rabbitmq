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

use Exception;
use PhpAmqpLib\Connection\AMQPLazyConnection;

/**
 *
 *
 *
 * @category   Thumper
 * @package    Thumper
 */
class RpcServer extends \Thumper\RpcServer {

    private $_dic;

    public function __construct($con_params) {
        $conn = new AMQPLazyConnection($con_params['host'], $con_params['port'], $con_params['user'], $con_params['password'], $con_params['vhost']);
        parent::__construct($conn);
    }

    public function setDic($dic) {
        $this->_dic = $dic;
    }

    public function processMessage($msg) {
        try {
            $body = json_decode($msg->body, true);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            $result = call_user_func($this->callback, $body, $msg->delivery_info, $this->_dic);
            $this->sendReply($result, $msg->get('reply_to'), $msg->get('correlation_id'));
        } catch (Exception $e) {
            $this->sendReply('error: ' . $e->getMessage(), $msg->get('reply_to'));
        }
    }

}
