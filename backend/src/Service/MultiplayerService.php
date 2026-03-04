<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\Session;
use App\Service\AssetService;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;

class MultiplayerService
{
    private int $room_count = 5;
    private array $rooms = [];
    private array $floors = [];

    public function __construct(
        private AssetService $asset_service,
        private PlayerRepository $player_repository,
        private EntityManagerInterface $em,
    ) {}

    private function add_session_to_collection(array &$collection, $key, Session $session)
    {
        if (!array_key_exists($key, $collection)) {
            $collection[$key] = [];
        }

        if ($session->on_disconnect !== null) {
            ($session->on_disconnect)();
        }

        $player_id = $session->data->player->get_player_id(); 
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
            
            if($s != $session) {
                $s->send([
                    'type' => 'server_new_player',
                    'player_id' => $session->data->player->get_player_id(),
                    'username' => $session->data->player->get_username(),
                    'img' => $session->data->player->get_img() ?? $this->asset_service->get_player_default(),
                    'pos' => [
                        'x' => $session->data->x,
                        'y' => $session->data->y,
                    ]
                ]);
                array_push($packets, [
                    'type' => 'server_new_player',
                    'player_id' => $s->data->player->getPlayerId(),
                    'username' => $s->data->player->getUsername(),
                    'img' => $s->data->player->get_img() ?? $this->asset_service->get_player_default(),
                    'pos' => [
                        'x' => $s->data->x,
                        'y' => $s->data->y,
                    ]
                ]);
            }
        }

        $collection[$key][] = $session;
        return $packets;
    }

    public function join_room(Session $session, Player $player): array
    {
        if (!$session->data->player) {
            throw new \Exception("unauthenticated player");
        }

        $session->data->room = $player->get_player_id();
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

    public function change_player_skin(Session $session, string $url) 
    {
        if (!$session->data->player) {
            throw new \Exception("unauthenticated player");
        }

        $session->data->player->set_img($url);
        
        $this->em->flush();
        
        $packet = [];
        if ($session->data->room !== null) {
            foreach($this->rooms as $rooms) {
                foreach($rooms as $s) {
                    if($s != $session) {
                        $s->send([
                            'type' => 'server_skin_update',
                            'player_id' => $session->data->player->get_player_id(),
                            'url' => $session->data->player->get_img(),
                        ]);
                    } else {
                        $packet = [
                            'type' => 'server_skin_update',
                            'player_id' => $session->data->player->get_player_id(),
                            'url' => $session->data->player->get_img(),
                        ];
                    }
                }
            }
        } elseif ($session->data->floor !== null) {
            foreach($this->floors as $floors) {
                foreach($floors as $s) {
                    if($s != $session) {
                        if($s != $session) {
                            $s->send([
                                'type' => 'server_skin_update',
                                'player_id' => $session->data->player->get_player_id(),
                                'url' => $session->data->player->get_img(),
                            ]);
                        }
                    } else {
                        $packet = [
                            'type' => 'server_skin_update',
                            'player_id' => $session->data->player->get_player_id(),
                            'url' => $session->data->player->get_img(),
                        ];
                    }
                }
            }
        }
        return $packet;

    }
    
    public function change_room_skin(Session $session, string $url)
    {
        if (!$session->data->player) {
            throw new \Exception("unauthenticated player");
        }
    
        $player = $session->data->player;
        $player->set_room_img($url);
    
        $this->em->flush();
    
        $packet = null;
        $playerId = $player->get_player_id();
    
        if (!empty($this->rooms[$playerId])) {
            foreach ($this->rooms[$playerId] as $s) {
    
                $data = [
                    'type' => 'server_room_skin_update',
                    'url' => $player->get_room_img(),
                ];
    
                if ($s !== $session) {
                    $s->send($data);
                } else {
                    $packet = $data;
                }
            }
        }
    
        return $packet;
    }
    
    public function send_chat_message(Session $session, string $message): array
    {
        if (!$session->data->player) {
            throw new \Exception("unauthenticated player");
        }

        if ($session->data->room !== null) {
            foreach($this->rooms as $rooms) {
                foreach($rooms as $s) {
                    if($s != $session) {
                        $s->send([
                            'type' => 'server_chat',
                            'player_id' => $session->data->player->get_player_id(),
                            'message' => $message,
                            'timeout' => 10,
                        ]);
                    }
                }
            }
        } elseif ($session->data->floor !== null) {
            foreach($this->floors as $floors) {
                foreach($floors as $s) {
                    if($s != $session) {
                        $s->send([
                            'type' => 'server_chat',
                            'player_id' => $session->data->player->get_player_id(),
                            'message' => $message,
                            'timeout' => 10,
                        ]);
                    }
                }
            }
        }

        return ([
            'type' => 'server_chat',
            'player_id' => $session->data->player->get_player_id(),
            'message' => $message,
            'timeout' => 10,
        ]);
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
                    'player_id' => $session->data->player->get_player_id(),
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