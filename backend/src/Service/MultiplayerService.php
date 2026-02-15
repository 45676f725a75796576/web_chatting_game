<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\Session;

use App\Service\AssetService;

class MultiplayerService
{
    private int $room_count = 5;
    private array $game = [];

    public function __construct(
        private AssetService $asset_service
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

        $session->on_disconnect = function() use ($session, $room_id) {
            $key = array_search($session, $this->game[$room_id], true);
            if ($key !== false) {
                unset($this->game[$room_id][$key]);
            }
        };

        foreach($this->game[$room_id] as $s) {
            $s->send([
                'type' => 'server_player_join',
                'player_id' => $s->data->player->getPlayerId(),
                'img' => $s->data->player->getImg() ?? $this->asset_service->getPlayerDefault()
            ]);
        }
        
        array_push($this->game[$room_id], $session);
    }
}