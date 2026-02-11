<?php

namespace App\Controller;

use App\Entity\Session;

interface PacketControllerInterface
{
    public function supports(string $type): bool;

    public function handle(Session $session, array $packet): void;
}
