<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Server implements MessageComponentInterface
{
    private \SplObjectStorage $clients;

    public function __construct(
        private PacketDispatcher $dispatcher
    ) {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $packets = explode("\n", $msg);

        foreach ($packets as $packetJson) {
            if (trim($packetJson) === '') {
                continue;
            }
            $packet = json_decode($packetJson, true);

            if (!is_array($packet)) {
                continue;
            }

            $this->dispatcher->dispatch($from, $packet);
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);

        $disconnectPacket = json_encode([
            "type" => "server_disconnect"
        ])."\n";

        foreach ($this->clients as $client) {
            $client->send($disconnectPacket);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();
    }
}
