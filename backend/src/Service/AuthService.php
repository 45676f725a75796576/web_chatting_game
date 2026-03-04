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
        $player = $this->player_repository->find_by_username_and_identifier($username, $identifier_str);
        $session->data->player = $player;
        return $player;
    }

    public function signin(Session $session, string $username, ?string $img = null): ?Player
    {
        $player = $this->player_repository->insert_player($username, $img);
        $session->data->player = $player;
        return $player;
    }
}