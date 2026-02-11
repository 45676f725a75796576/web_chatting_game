<?php

namespace App\WebSocket;

use Ratchet\ConnectionInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use App\Entity\Session;

class PacketDispatcher
{
    public function __construct(
        #[TaggedIterator('app.packet_controller')]
        private iterable $handlers
    ) {}

    public function dispatch(Session $session, array $packet): void
    {
        if (!isset($packet['type'])) {
            return;
        }

        $type = $packet['type'];

        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                $handler->handle($session, $packet);
                return;
            }
        }

        $session->conn->send(json_encode([
            'type' => 'server_error',
            'message' => 'Unknown packet type'
        ])."\n");
    }
}
