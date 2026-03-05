<?php

namespace App\Entity;

use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;

class Session 
{
    public function __construct(
        public ClientData $data,
        public ConnectionInterface $conn,
        private LoggerInterface $logger
    ) {}
   
    public ?\Closure $on_disconnect = null;

    public function send(array $packet): void
    {
        $this->logger->info('packet received', [
            'packet' => $packet,
        ]);
        $this->conn->send(json_encode($packet)."\n");
    }
    
    public function disconnect(): void
    {
        $this->conn->close();
    }
}