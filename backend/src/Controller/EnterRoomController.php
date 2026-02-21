<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\MultiplayerService;
use App\Service\AssetService;

class EnterRoomController extends AbstractPacketController
{
    public function __construct(
        private PlayerRepository $player_repository,
        private MultiplayerService $multiplayer_service,
        private AssetService $asset_service
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'enter_room';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send([
                'type' => 'server_room',
                'state' =>'error',
                'message' => 'user is not authenticated'
            ]);
            return;
        }

        $room_id = $packet['room_id'];

        $dest_player = $this->player_repository->findById($room_id);
        if(!$dest_player) {
            $session->send([
                'type' => 'server_room',
                'state' =>'error',
                'message' => 'room not found'
            ]);
        }


        $packets = null;
        try {
            $packets = $this->multiplayer_service->join_room($session, $dest_player);
        } catch (\Throwable $e) {
            $this->send($session, [
                'type' => 'server_room',
                'state' => 'error',
                'message' => 'failed to join the room',
            ]);
            return;
        }

        $session->send([
            'type' => 'server_room',
            'place' => [
                'img' => $dest_player->getRoomImg() ?? $this->asset_service->getRoomDefault(),
                'room_id' => $dest_player->getPlayerId(),
                'floor' => $this->multiplayer_service->get_floor($dest_player->getPlayerId())
            ]
        ]);

        foreach($packets as $p) {
            $session->send($p);
        }
    }
}