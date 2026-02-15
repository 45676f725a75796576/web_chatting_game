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
        if(!$session->data)
        {
            $session->send([
                'type' => 'server_enter_game',
                'state' =>'error',
                'message' => 'user is not authenticated'
            ]);
            return;
        }

        $this->multiplayer_service->join_room($session);

        $player = $session->data->player;
        $session->send([
                'type' => 'server_place',
                'place' => [
                    'img' => $player->getRoomImg() ?? $this->asset_service->getRoomDefault(),
                    'id' => $player->getPlayerId(),
                    'is_floor' => false
                ]
            ]);
    }
}