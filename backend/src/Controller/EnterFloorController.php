<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\MultiplayerService;
use App\Service\AssetService;

class EnterFloorController extends AbstractPacketController
{
    public function __construct(
        private PlayerRepository $player_repository,
        private MultiplayerService $multiplayer_service,
        private AssetService $asset_service
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'enter_floor';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send([
                'type' => 'server_floor',
                'state' =>'error',
                'message' => 'user is not authenticated'
            ]);
            return;
        }

        $floor_id = $packet['floor_id'];

        $packets = null;
        try {
            $packets = $this->multiplayer_service->join_floor($session, $floor_id);
        } catch (\Throwable $e) {
            $this->send($session, [
                'type' => 'server_floor',
                'state' => 'error',
                'message' => $e->getMessage(),
            ]);
            return;
        }
            
        $session->send([
            'type' => 'server_place',
            'img' => $this->asset_service->getFloor(),
            'floor_id' => $floor_id,
            'rooms' => $this->multiplayer_service->get_rooms($floor_id)
        ]);

        foreach($packets as $p) {
            $session->send($p);
        }
    }
}