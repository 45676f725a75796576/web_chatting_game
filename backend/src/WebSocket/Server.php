<?php

namespace App\WebSocket;

use App\Entity\ClientData;
use App\Entity\Session;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Server implements MessageComponentInterface
{
    private \SplObjectStorage $clients;
    private array $sessions = [];

    public function __construct(
        private PacketDispatcher $dispatcher
    ) {
        $this->clients = new \SplObjectStorage();
        $this->sessions = [];
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        $this->sessions[$conn->resourceId] = new Session(new ClientData(), $conn);
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
        
        $this->sessions[$conn->resourceId] = new ClientData();
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        unset($this->sessions[$conn->resourceId]);
        $conn->close();
    }
}