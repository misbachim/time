<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Webpatser\Uuid\Uuid;

function send($sender, $msgType, $receiver, $uid = null, $data = null)
{
    $uid = ($uid === null) ? md5(Uuid::generate()) : $uid;
    $routingKey = "$sender.$msgType.$receiver.$uid";
    $msg = new AMQPMessage(json_encode([
        'uid' => $uid,
        'sender' => $sender,
        'msgType' => $msgType,
        'data' => $data
    ]));

    $rabbitmqConn = new AMQPStreamConnection(
        env('RABBITMQ_HOST'),
        env('RABBITMQ_PORT'),
        env('RABBITMQ_LOGIN'),
        env('RABBITMQ_PASSWORD')
    );
    $rabbitmqChannel = $rabbitmqConn->channel();
    $rabbitmqChannel->exchange_declare(
        'topic_messages',
        'topic',
        false,
        false,
        false
    );

    $rabbitmqChannel->basic_publish(
        $msg,
        'topic_messages',
        $routingKey
    );
    $rabbitmqChannel->close();
    $rabbitmqConn->close();
    info('Sent message', ['routing key' => $routingKey, 'message' => $msg]);

    return $uid;
}

function receive($receiver, $msgType, $uid, $callback)
{
    $queue = env('RABBITMQ_LISTEN_QUEUE');
    $bindingKey = "*.$msgType.$receiver.$uid";
    info("Listening at $bindingKey");
    $rabbitmqConn = new AMQPStreamConnection(
        env('RABBITMQ_HOST'),
        env('RABBITMQ_PORT'),
        env('RABBITMQ_LOGIN'),
        env('RABBITMQ_PASSWORD')
    );
    $rabbitmqChannel = $rabbitmqConn->channel();
    $rabbitmqChannel->exchange_declare(
        'topic_messages',
        'topic',
        false,
        false,
        false
    );
    $rabbitmqChannel->queue_declare(
        $queue,
        false,
        false,
        true,
        false
    );
    $rabbitmqChannel->queue_bind(
        $queue,
        'topic_messages',
        $bindingKey
    );

    $rabbitmqChannel->basic_consume(
        $queue,
        '',
        false,
        true,
        false,
        false,
        function ($msg) use ($receiver, $callback) {
            $msg = json_decode($msg->body);
            info("Received message", (array) $msg);
            call_user_func($callback, $msg);
        }
    );
    while (count($rabbitmqChannel->callbacks)) {
        $rabbitmqChannel->wait();
    }
    $rabbitmqChannel->close();
    $rabbitmqConn->close();
}

function exchangeOnce(
    $sender,
    $msgType,
    $receiver,
    $uid = null,
    $data = null,
    $callback
) {
    $uid = ($uid === null) ? md5(Uuid::generate()) : $uid;
    $msg = new AMQPMessage(json_encode([
        'uid' => $uid,
        'sender' => $sender,
        'msgType' => $msgType,
        'data' => $data
    ]));

    $rabbitmqConn = new AMQPStreamConnection(
        env('RABBITMQ_HOST'),
        env('RABBITMQ_PORT'),
        env('RABBITMQ_LOGIN'),
        env('RABBITMQ_PASSWORD')
    );
    $rabbitmqChannel = $rabbitmqConn->channel();
    $rabbitmqChannel->exchange_declare(
        'topic_messages',
        'topic',
        false,
        false,
        false
    );

    $queue = env('RABBITMQ_RESPONSE_QUEUE');
    $bindingKey = "*.$msgType.$sender.$uid";
    info("Listening at $bindingKey");
    $rabbitmqChannel->queue_declare(
        $queue,
        false,
        false,
        true,
        false
    );
    $rabbitmqChannel->queue_bind(
        $queue,
        'topic_messages',
        $bindingKey
    );

    $routingKey = "$sender.$msgType.$receiver.$uid";
    $rabbitmqChannel->basic_publish(
        $msg,
        'topic_messages',
        $routingKey
    );
    info('Sent message', ['routing key' => $routingKey, 'message' => $msg]);

    $rabbitmqChannel->basic_consume(
        $queue,
        '',
        false,
        true,
        false,
        false,
        function ($msg) use ($receiver, $callback) {
            $msg = json_decode($msg->body);
            info("Received message", (array) $msg);
            call_user_func($callback, $msg);
        }
    );
    if (count($rabbitmqChannel->callbacks) > 0) {
        $rabbitmqChannel->wait();
    }
    $rabbitmqChannel->close();
    $rabbitmqConn->close();
}
