<?php

namespace App\Service;

use App\Repository\PlayerRepository;
use App\Entity\Player;
use App\Entity\Session;

class AuthService
{
    public function __construct(
        private PlayerRepository $player_repository
    ) {}

    public function login(Session $session, string $username, string $identifier_str): ?Player
    {
        $player = $this->player_repository->findByUsernameAndIdentifier($username, $identifier_str);
        $session->data->player = $player;
        return $player;
    }

    public function signin(Session $session, string $username, ?string $img = null): ?Player
    {
        $player = $this->player_repository->insertPlayer($username, $img);
        $session->data->player = $player;
        return $player;
    }
}