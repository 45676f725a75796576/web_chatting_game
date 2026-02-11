<?php

namespace App\WebSocket\Packet;

use Ratchet\ConnectionInterface;

abstract class AbstractPacketHandler implements PacketHandlerInterface
{
    protected function send(ConnectionInterface $conn, array $packet): void
    {
        $conn->send(json_encode($packet)."\n");
    }
}
