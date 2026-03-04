<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\MultiplayerService;
use App\Service\AssetService;
use App\Service\PacketService;
use Psr\Log\LoggerInterface;

class EnterGameController extends AbstractPacketController
{
    public function __construct(
        private MultiplayerService $multiplayer_service,
        private AssetService $asset_service,
        private PacketService $packet_service,
        private LoggerInterface $logger
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'enter_game';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send($this->packet_service->server_error('user is not authenticated'));
            return;
        }

        $packets = null;
        try {
            $player = $session->data->player;
            try {
                $packets = $this->multiplayer_service->join_room($session, $player);
            } catch(\Throwable $e) {
                $session->send($this->packet_service->server_error('room is locked'));
                return;
            }
        } catch (\Throwable $ex) {
            $this->logger->error('Exception occurred', [
                'exception' => $ex->getMessage(),
            ]);
            $session->send($this->packet_service->server_error('failed to join the room'));
            return;
        }

        $session->send($this->packet_service->server_room(
            $player->get_room_img() ?? $this->asset_service->get_room_default(),
            $this->multiplayer_service->get_player_room($player),
            $player->get_username(),
            $this->multiplayer_service->get_floor($player->get_player_id())
        ));

        foreach($packets as $p) {
            $session->send($p);
        }
    }
}