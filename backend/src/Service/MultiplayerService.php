<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\Session;
use App\Service\AssetService;

class MultiplayerService
{
    private int $room_count = 5;
    private array $rooms = [];
    private array $floors = [];

    public function __construct(
        private AssetService $asset_service
    ) {}

    private function addSessionToCollection(array &$collection, $key, Session $session)
    {
        if (!array_key_exists($key, $collection)) {
            $collection[$key] = [];
        }

        if ($session->on_disconnect !== null) {
            ($session->on_disconnect)();
        }

        $player_id = $session->data->player->getPlayerId(); 
        $session->on_disconnect = function() use (&$collection, $key, $session, $player_id) {
            $index = array_search($session, $collection[$key], true);
            if ($index !== false) {
                unset($collection[$key][$index]);
                $collection[$key] = array_values($collection[$key]);
            }

            foreach ($collection[$key] as $s) {
                $s->send([
                    'type' => 'server_disconnect',
                    'player_id' => $player_id
                ]);
            }
        };

        $packets = [];
        foreach ($collection[$key] as $s) {
            $s->send([
                'type' => 'server_player_join',
                'player_id' => $session->data->player->getPlayerId(),
                'img' => $session->data->player->getImg() ?? $this->asset_service->getPlayerDefault()
            ]);

            array_push($packets, [
                'type' => 'server_player_join',
                'player_id' => $s->data->player->getPlayerId(),
                'img' => $s->data->player->getImg() ?? $this->asset_service->getPlayerDefault()
            ]);
        }

        $collection[$key][] = $session;
        return $packets;
    }

    public function join_room(Session $session, Player $player): array
    {
        if (!$session->data->player) {
            throw new \Exception("unauthenticated player");
        }

        return $this->addSessionToCollection($this->rooms, $player->getPlayerId(), $session);
    }

    public function join_floor(Session $session, int $floor_id): array
    {
        if (!$session->data->player) {
            throw new \Exception("unauthenticated player");
        }

        return $this->addSessionToCollection($this->floors, $floor_id, $session);
    }
}