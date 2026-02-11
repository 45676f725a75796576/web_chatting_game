<?php

namespace App\WebSocket\Packet;

use Ratchet\ConnectionInterface;

interface PacketHandlerInterface
{
    public function supports(string $type): bool;

    public function handle(ConnectionInterface $connection, array $packet): void;
}
