<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\Session;
use App\Service\AssetService;
use App\Repository\PlayerRepository;

class MultiplayerService
{
    private int $room_count = 5;
    private array $rooms = [];
    private array $floors = [];

    public function __construct(
        private AssetService $asset_service,
        private PlayerRepository $player_repository
    ) {}

    private function add_session_to_collection(array &$collection, $key, Session $session)
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
            
            $s->send([
                'type' => 'server_player_pos',
                'player_id' => $session->data->player->getPlayerId(),
                'pos' => [
                    'x' => $session->data->x,
                    'y' => $session->data->y,
                ]
            ]);

            array_push($packets, [
                'type' => 'server_player_join',
                'player_id' => $s->data->player->getPlayerId(),
                'img' => $s->data->player->getImg() ?? $this->asset_service->getPlayerDefault()
            ]);
            
            array_push($packets, [
                'type' => 'server_player_pos',
                'player_id' => $s->data->player->getPlayerId(),
                'pos' => [
                    'x' => $s->data->x,
                    'y' => $s->data->y,
                ]
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

        $session->data->room = $player->getPlayerId();
        $session->data->floor = null;

        $session->data->x = 0;
        $session->data->y = 0;
        return $this->add_session_to_collection($this->rooms, $player->getPlayerId(), $session);
    }

    public function join_floor(Session $session, int $floor_id): array
    {
        if (!$session->data->player) {
            throw new \Exception("unauthenticated player");
        }

        $session->data->floor = $floor_id;
        $session->data->room = null;
        
        $session->data->x = 0;
        $session->data->y = 0;
        return $this->add_session_to_collection($this->floors, $floor_id, $session);
    }

    public function update_player_pos(Session $session, int $x, int $y)
    {
        if (!$session->data->player) {
            throw new \Exception("unauthenticated player");
        }

        if ($session->data->room !== null) {
            $this->change_player_pos_in_collection($this->rooms, $session->data->room, $session, $x, $y);
        } elseif ($session->data->floor !== null) {
            $this->change_player_pos_in_collection($this->floors, $session->data->floor, $session, $x, $y);
        }

        $session->data->x = $x;
        $session->data->y = $y;
    }

    private function change_player_pos_in_collection(array &$collection, $key, Session $session, int $x, int $y)
    {
        if (!isset($collection[$key])) {
            return;
        }

        foreach ($collection[$key] as $s) {
            if ($s !== $session) {
                $s->send([
                    'type' => 'server_player_pos',
                    'player_id' => $session->data->player->getPlayerId(),
                    'pos' => [
                        'x' => $x,
                        'y' => $y,
                    ]
                ]);
            }
        }
    }

    public function get_floor(int $room_id) 
    {
        return (int)($room_id / $this->room_count);
    }

    public function get_rooms(int $floor_id)
    {
        $floor_rooms = [];
        for($i = 1; $i < $this->room_count + 1; $i++) {
            $id = $i + $floor_id;
            $player = $this->player_repository->findById($id);
            if($player != null) {
                array_push($floor_rooms, (string)$id);
            }
        }

        return $floor_rooms;
    }
}