<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\MultiplayerService;
use App\Service\AssetService;
use App\Service\PacketService;
use Psr\Log\LoggerInterface;

class EnterRoomController extends AbstractPacketController
{
    public function __construct(
        private PlayerRepository $player_repository,
        private MultiplayerService $multiplayer_service,
        private AssetService $asset_service,
        private PacketService $packet_service,
        private LoggerInterface $logger
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'enter_room';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send($this->packet_service->server_error('user is not authenticated'));
            return;
        }

        $room_id = $packet['room_id'];

        if(!$room_id) {
            $session->send($this->packet_service->server_error('missing room_id'));
            return;
        }

        $dest_player = $this->player_repository->find_by_id($room_id);
        if(!$dest_player) {
            $session->send($this->packet_service->server_error('room not found'));
            return;
        }

        $packets = null;
        try {
            try {
                $packets = $this->multiplayer_service->join_room($session, $dest_player);
            } catch(\Throwable $e) {
                $session->send($this->packet_service->server_error('room is locked'));
                return;
            }

        } catch (\Throwable $e) {
            $this->logger->error('Exception occurred', [
                'exception' => $e->getMessage(),
            ]);
            $session->send($this->packet_service->server_error('failed to join the room'));
            return;
        }

        $session->send($this->packet_service->server_room(
            $dest_player->get_room_img() ?? $this->asset_service->get_room_default(),
            $dest_player->get_player_id(),
            $dest_player->get_username(),
            $this->multiplayer_service->get_floor($dest_player->get_player_id())
        ));

        if($packets != null) {
            foreach($packets as $p) {
                $session->send($p);
            }
        }
    }
}