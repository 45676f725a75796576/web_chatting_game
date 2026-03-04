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
            'img' => $player->get_room_img() ?? $this->asset_service->get_room_default(),
            'room_id' => $player->get_player_id(),
            "floor" => $this->multiplayer_service->get_floor($player->get_player_id())
        ]);
            
        foreach($packets as $p) {
            $session->send($p);
        }
    }
}