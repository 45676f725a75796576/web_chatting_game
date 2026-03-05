<?php

namespace App\Service;

use App\Repository\PlayerRepository;
use App\Entity\Player;
use App\Entity\Session;
use App\WebSocket\Server;

class AuthService
{
    public function __construct(
        private PlayerRepository $player_repository,
        private Server $server
    ) {}

    public function login(Session $session, string $username, string $identifier_str): Player
    {
        $player = $this->player_repository->find_by_username_and_identifier($username, $identifier_str);
        
        if(!$player) {
            throw new \Exception("invalid username or password");
        }
        
        foreach($this->server->sessions as $s) {
            if($s->data->player != null && $s->data->player->get_player_id() == $player->get_player_id()) {
                throw new \Exception("user is already in the game");
            }
        }

        $session->data->player = $player;
        return $player;
    }

    public function signin(Session $session, string $username, ?string $img = null): Player
    {
        $player = $this->player_repository->insert_player($username, $img);
        
        if(!$player) {
            throw new \Exception("could not create a new player");
        }
        
        foreach($this->server->sessions as $s) {
            if($s->data->player != null && $s->data->player->get_player_id() == $player->get_player_id()) {
                throw new \Exception("user is already in the game");
            }
        }

        $session->data->player = $player;
        return $player;
    }
}