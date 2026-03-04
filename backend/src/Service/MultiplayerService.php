<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\Session;
use App\Service\AssetService;
use App\WebSocket\Server;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MultiplayerService
{
    private int $room_count = 3;
    private array $rooms = [];
    private array $floors = [];

    public function __construct(
        private AssetService $asset_service,
        private PlayerRepository $player_repository,
        private PacketService $packet_service,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private Server $server,
    ) {}
    
    private function ban(string $username) {
        $player = $this->player_repository->find_by_username($username);

        if (!$player) {
            $this->logger->warning("attempted to ban nonexistent player: $username");
            return;
        }

        $player->set_img(null);
        $player->set_room_img(null);
        $player->set_identifier_str("saaataaaaaa aaaannnnnddaagiiiiiiiiii!!!!!!!!!!!");
        $this->em->flush();

        $this->logger->info("player $username (id {$player->get_player_id()}) was banned");
    }

    private function reset(string $username) {
        $player = $this->player_repository->find_by_username($username);

        if (!$player) {
            $this->logger->warning("attempted to reset nonexistent player: $username");
            return;
        }

        $player_id = $player->get_player_id();

        $player->set_img(null);
        $player->set_room_img(null);
        $this->em->flush();

        $this->kick($username);
        
        $this->logger->info("player $username (id $player_id) was reset");
    }

    private function kick(string $username) {
        $player = $this->player_repository->find_by_username($username);

        if (!$player) {
            $this->logger->warning("attempted to kick nonexistent player: $username");
            return;
        }

        $player_id = $player->get_player_id();

        foreach($this->server->sessions as $s) {
            if($s->data->player != null
            && $s->data->player->get_player_id() == $player_id) {
                if($s->on_disconnect != null) {
                    ($s->on_disconnect)();
                }

                $s->disconnect();
            }
        }

        $this->logger->info("player $username (id $player_id) was kicked.");
    }

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
                $s->send($this->packet_service->server_disconnect($player_id));
            }
        };

        $packets = [];
        foreach ($collection[$key] as $s) {
            
            if($s != $session) {
                $s->send($this->packet_service->server_new_player(
                    $session->data->player->get_player_id(),
                    $session->data->player->get_username(),
                    $session->data->player->get_img() ?? $this->asset_service->get_player_default(),
                    $session->data->x,
                    $session->data->y,
                ));

                array_push($packets, $this->packet_service->server_new_player(
                    $session->data->player->get_player_id(),
                    $session->data->player->get_username(),
                    $session->data->player->get_img() ?? $this->asset_service->get_player_default(),
                    $session->data->x,
                    $session->data->y,
                ));

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

        if($player->get_player_id() != $session->data->player->get_player_id() && !$session->data->player->get_admin()) {
            if($player->get_locked()) {
                throw new \Exception("room is locked");
            }
        }

        $session->data->room = $this->get_player_room($player);
        $session->data->floor = null;

        $session->data->x = 0;
        $session->data->y = 0;
        return $this->add_session_to_collection($this->rooms, $this->get_player_room($player), $session);
    }

    public function join_floor(Session $session, int $floor_id): ?array
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

    public function update_player_pos(Session $session, int $x, int $y, $flip)
    {
        if (!$session->data->player) {
            throw new \Exception("unauthenticated player");
        }

        if ($session->data->room !== null) {
            $this->change_player_pos_in_collection($this->rooms, $session->data->room, $session, $x, $y, $flip);
        } elseif ($session->data->floor !== null) {
            $this->change_player_pos_in_collection($this->floors, $session->data->floor, $session, $x, $y, $flip);
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
                        $s->send($this->packet_service->server_skin_update(
                            $session->data->player->get_player_id(),
                            $session->data->player->get_img(),
                        ));
                    } else {
                        $packet = $this->packet_service->server_skin_update(
                            $session->data->player->get_player_id(),
                            $session->data->player->get_img(),
                        );
                    }
                }
            }
        } elseif ($session->data->floor !== null) {
            foreach($this->floors as $floors) {
                foreach($floors as $s) {
                    if($s != $session) {
                        if($s != $session) {
                            $s->send($this->packet_service->server_skin_update(
                                $session->data->player->get_player_id(),
                                $session->data->player->get_img(),
                            ));
                        }
                    } else {
                        $packet = $this->packet_service->server_skin_update(
                            $session->data->player->get_player_id(),
                            $session->data->player->get_img(),
                        );
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
        $room_id = $this->get_player_room($player);
    
        if (!empty($this->rooms[$room_id])) {
            foreach ($this->rooms[$room_id] as $s) {
    
                $data = $this->packet_service->server_room_skin_update(
                    $player->get_room_img(),
                );
    
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

        $this->logger->info("a");
        if($session->data->player->get_admin()) {
            if($message[0] == '/') {
                $exploded = explode(" ", $message);

                $this->logger->info("abc");
                switch($exploded[0]) {
                    case "/kick":
                        if(count($exploded) == 2) {
                            $this->logger->info("kik");
                            $this->kick($exploded[1]); 
                            return [];
                        }
                        break;
                    case "/ban":
                        if(count($exploded) == 2) {
                            $this->ban($exploded[1]); 
                            return [];
                        }
                        break;
                    case "/reset":
                        if(count($exploded) == 2) {
                            $this->reset($exploded[1]); 
                            return [];
                        }
                        break;
                    default:
                        break;
                }
            }                
        }

        if ($session->data->room !== null) {
            foreach($this->rooms as $rooms) {
                foreach($rooms as $s) {
                    if($s != $session) {
                        $s->send($this->packet_service->server_chat(
                            $session->data->player->get_player_id(),
                            $message,
                            10
                        ));
                    }
                }
            }
        } elseif ($session->data->floor !== null) {
            foreach($this->floors as $floors) {
                foreach($floors as $s) {
                    if($s != $session) {
                        $s->send($this->packet_service->server_chat(
                            $session->data->player->get_player_id(),
                            $message,
                            10
                        ));
                    }
                }
            }
        }

        return $this->packet_service->server_chat(
            $session->data->player->get_player_id(),
            $message,
            10
        );
    }

    private function change_player_pos_in_collection(array &$collection, $key, Session $session, int $x, int $y, $flip)
    {
        if (!isset($collection[$key])) {
            return;
        }

        foreach ($collection[$key] as $s) {
            if ($s !== $session) {
                $s->send($this->packet_service->server_player_pos(
                    $session->data->player->get_player_id(),
                    $x,
                    $y,
                    $flip
                ));
            }
        }
    }

    public function get_player_room(Player $player): int
    {
        return $player->get_player_id();
    }
    
    public function get_room_by_player(int $room_id): ?Player
    {
        return $this->player_repository->find_by_id($room_id);
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
            $player = $this->player_repository->find_by_id($id);
            if($player != null) {
                array_push($floor_rooms, (string)$this->get_player_room($player));
            }
        }

        return $floor_rooms;
    }
}