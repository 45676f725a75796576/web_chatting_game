<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\MultiplayerService;
use App\Service\AssetService;

class EnterGameController extends AbstractPacketController
{
    public function __construct(
        private MultiplayerService $multiplayer_service,
        private AssetService $asset_service
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'enter_game';
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

        $player = null;
        $packets = null;
        try {
            $player = $session->data->player;
            $packets = $this->multiplayer_service->join_room($session, $player);
            if($packets == null) {
                $this->send($session, [
                    'type' => 'server_room',
                    'state' => 'error',
                    'message' => 'room is locked',
                ]);
                return;
            }
        } catch (\Throwable $e) {
            $this->send($session, [
                'type' => 'server_room',
                'state' => 'error',
                'message' => $e->getMessage(),
            ]);
            return;
        }

        $session->send([
            'type' => 'server_room',
            'state' => 'success',
            'img' => $player->getRoomImg() ?? $this->asset_service->getRoomDefault(),
            'room_id' => $player->getPlayerId(),
            "floor" => $this->multiplayer_service->get_floor($player->getPlayerId())
        ]);
            
        foreach($packets as $p) {
            $session->send($p);
        }
    }
}