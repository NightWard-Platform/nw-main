<?php

namespace app;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\Component;

class RabbitMq extends Component
{
    public $host = 'rabbitmq';
    public $port = 5672;
    public $user = 'admin';
    public $password = 'admin';
    public $vhost = '/';
    private $_connection;
    private $_channel;
    public function init()
    {
        parent::init();

        $this->_connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->vhost
        );
        $this->_channel = $this->_connection->channel();
    }
    public function publish($queue, $message)
    {
        $this->_channel->queue_declare($queue, false, true, false, false);
        $msg = new AMQPMessage($message);
        $this->_channel->basic_publish($msg, '', $queue);
    }
    public function consume($queue, $callback)
    {
        $this->_channel->queue_declare($queue, false, true, false, false);
        $this->_channel->basic_consume($queue, '', false, true, false, false, $callback);
        while ($this->_channel->is_consuming()) {
            $this->_channel->wait();
        }
    }
    public function __destruct()
    {
        if ($this->_channel) $this->_channel->close();
        if ($this->_connection) $this->_connection->close();
    }
}
