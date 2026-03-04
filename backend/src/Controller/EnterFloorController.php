<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\MultiplayerService;
use App\Service\AssetService;
use App\Service\PacketService;


class EnterFloorController extends AbstractPacketController
{
    public function __construct(
        private MultiplayerService $multiplayer_service,
        private AssetService $asset_service,
        private PacketService $packet_service
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'enter_floor';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send($this->packet_service->server_error('user is not authenticated'));
            return;
        }

        $floor_id = $packet['floor_id'];

        if(!$floor_id) {
            $session->send($this->packet_service->server_error('missing floor_id'));
            return;
        }



        $packets = null;
        try {
            $packets = $this->multiplayer_service->join_floor($session, $floor_id);
        } catch (\Throwable $e) {
            $session->send($this->packet_service->server_error('failed to join floor'));
            return;
        }
            
        $session->send($this->packet_service->server_floor(
            $this->asset_service->get_floor(),
            $floor_id,
            $this->multiplayer_service->get_rooms($floor_id)
        ));

        foreach($packets as $p) {
            $session->send($p);
        }
    }
}