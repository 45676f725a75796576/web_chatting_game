<?php

namespace App\Entity;

use Ratchet\ConnectionInterface;

class Session 
{
    public function __construct(
        public ClientData $data,
        public ConnectionInterface $conn
    ) {}
}