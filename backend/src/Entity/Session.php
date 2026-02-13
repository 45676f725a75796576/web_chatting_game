<?php

namespace App\Entity;

use Ratchet\ConnectionInterface;

class Session 
{
    public function __construct(
        public ClientData $data,
        public ConnectionInterface $conn
    ) {}
   
    public ?\Closure $on_disconnect = null;

    public function send(array $packet): void
    {
        $this->conn->send(json_encode($packet)."\n");
    }
}