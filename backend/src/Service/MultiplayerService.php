<?php

namespace App\Service;

use App\Entity\Player;

class MultiplayerService
{
    private int room_count = 5;
    private array $game = [];

    public function __construct(
       
    ) {}

    public function join_room(Session $session)
    {
        if(!$session->data->player) {
            throw new \Exception("unauthenticated player");
            return;
        }

        $player = $session->data->player;

        $room_id = $player->getPlayerId();
        if(!array_key_exists($this->game)) {
            $this->game[$room_id] = [];
        }

        array_push($this->game[$room_id], $player);

        foreach($array as $s) {
            $session->send([
                'type' => 'server_player_join',
                'player_id' => $palyer->getPlayerId(),
                'img' => $player->getImg() ?? 'TODO: add url here'
            ]);
        }
    }
}