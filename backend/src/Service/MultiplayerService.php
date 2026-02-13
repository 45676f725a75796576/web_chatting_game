<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\Session;

class MultiplayerService
{
    private int $room_count = 5;
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
        if(!array_key_exists($room_id, $this->game)) {
            $this->game[$room_id] = [];
        }

        $session->on_disconnect = function() use ($player) {
            unset($this->game[$player->getPlayerId()]);
        };

        array_push($this->game[$room_id], $player);

        foreach($this->game as $s) {
            $s->send([
                'type' => 'server_player_join',
                'player_id' => $s->data->player->getPlayerId(),
                'img' => $s->data->player->getImg() ?? 'TODO: add url here'
            ]);
        }
    }
}