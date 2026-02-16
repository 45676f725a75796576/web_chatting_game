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
                'type' => 'server_place',
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
                'type' => 'server_place',
                'state' => 'error',
                'message' => $e->getMessage(),
            ]);
            return;
        }

        $session->send([
            'type' => 'server_place',
            'state' => 'success',
            'place' => [
                'img' => $player->getRoomImg() ?? $this->asset_service->getRoomDefault(),
                'id' => $player->getPlayerId(),
                'is_floor' => false
            ]
        ]);
            
        foreach($packets as $p) {
            $session->send($p);
        }
    }
}