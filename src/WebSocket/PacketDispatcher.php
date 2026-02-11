<?php

namespace App\WebSocket;

use Ratchet\ConnectionInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class PacketDispatcher
{
    public function __construct(
        #[TaggedIterator('app.packet_handler')]
        private iterable $handlers
    ) {}

    public function dispatch(ConnectionInterface $connection, array $packet): void
    {
        if (!isset($packet['type'])) {
            return;
        }

        $type = $packet['type'];

        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                $handler->handle($connection, $packet);
                return;
            }
        }

        $connection->send(json_encode([
            'type' => 'server_error',
            'message' => 'Unknown packet type'
        ])."\n");
    }
}
