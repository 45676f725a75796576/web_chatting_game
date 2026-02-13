<?php

namespace App\Entity;

use Ratchet\ConnectionInterface;

class Session 
{
    public function __construct(
        public ClientData $data,
        public ConnectionInterface $conn
    ) {}
    
    public function send(array $packet): void
    {
        $this->conn->send(json_encode($packet)."\n");
    }
}