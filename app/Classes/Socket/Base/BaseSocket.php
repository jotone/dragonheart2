<?php
namespace App\Classes\Socket\Base;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ComponentInterface;

class BaseSocket implements MessageComponentInterface
{
    function onOpen(ConnectionInterface $conn)
    {
        // TODO: Implement onOpen() method.
    }

    function onClose(ConnectionInterface $conn)
    {
        // TODO: Implement onClose() method.
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        // TODO: Implement onError() method.
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
        // TODO: Implement onMessage() method.
    }

}