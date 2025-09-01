<?php

namespace common\components;

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

    public $exchange = 'db.events';
    public $dlxExchange = 'db.dlx';

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

        $this->_channel->exchange_declare($this->exchange, 'topic', false, true, false);

        $this->_channel->exchange_declare($this->dlxExchange, 'fanout', false, true, false);
    }
    public function exchange_publish(string $routingKey, string $message, array $headers = [])
    {
        $msg = new AMQPMessage($message, [
            'delivery_mode' => 2, //2 - persistent
            'application_headers' => $headers
        ]);
        $this->_channel->basic_publish($msg, $this->exchange, $routingKey);
    }
    public function publish($queue, $message)
    {
        $this->_channel->queue_declare($queue, false, true, false, false);
        $msg = new AMQPMessage($message);
        $this->_channel->basic_publish($msg, '', $queue);
    }
    public function consume(string $queue, array $bindingKeys, callable $callback)
    {
        $this->_channel->queue_declare($queue, false, true, false, false, false, [
            'x-dead-letter-exchange' => ['S', $this->dlxExchange]
        ]);
        $retryQueue = "retry.$queue";
        $this->_channel->queue_declare($retryQueue, false, true, false, false, false, [
            'x-dead-letter-exchange' => ['S', ''],
            'x-dead-letter-routing-key' => ['S', $queue],
            'x-message-ttl' => ['I', 10000], //10 sec 
        ]);
        $this->_channel->queue_bind($retryQueue, $this->dlxExchange);

        foreach ($bindingKeys as $key) {
            $this->_channel->queue_bind($queue, $this->exchange, $key);
        }
        $this->_channel->basic_consume($queue, '', false, false, false, false, function ($msg) use ($callback) {
            try {
                $callback($msg->body);
                $msg->ack();
            } catch (\Throwable $e) {
                $msg->nack(false, false);
            }
        });

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
