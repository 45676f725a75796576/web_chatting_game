<?php

namespace App\Controller;

use App\Entity\Session;
use Ratchet\ConnectionInterface;
use React\Socket\Connector;

abstract class AbstractPacketController implements PacketControllerInterface
{
    protected function send(Session $session, array $packet): void
    {
        $session->conn->send(json_encode($packet)."\n");
    }
}
