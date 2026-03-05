<?php

namespace App\WebSocket;

use App\Entity\ClientData;
use App\Entity\Session;
use Psr\Log\LoggerInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Server implements MessageComponentInterface
{
    private \SplObjectStorage $clients;
    public array $sessions = [];

    public function __construct(
        private PacketDispatcher $dispatcher,
        private LoggerInterface $logger
    ) {
        $this->clients = new \SplObjectStorage();
        $this->sessions = [];
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        $this->sessions[$conn->resourceId] = new Session(new ClientData(), $conn, $this->logger);
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

            $this->dispatcher->dispatch($this->sessions[$from->resourceId], $packet);
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        
        $session = $this->sessions[$conn->resourceId];
        
        if($session->on_disconnect != null) {
            ($session->on_disconnect)();
        }
        
        unset($this->sessions[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        unset($this->sessions[$conn->resourceId]);
        $conn->close();
    }
}