<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\AuthService;
use App\Service\AssetService;
use App\Service\MultiplayerService;
use App\Service\PacketService;
use Psr\Log\LoggerInterface;

class LoginController extends AbstractPacketController
{
    public function __construct(
        private AuthService $auth_service,
        private AssetService $asset_service,
        private PacketService $packet_service,
        private MultiplayerService $multiplayer_service,
        private LoggerInterface $logger
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'login';
    }

    public function handle(Session $session, array $packet): void
    {
        $identifier_str = $packet['identifier_str'] ?? null;
        $username = $packet['username'] ?? null;

        if(!$identifier_str || !$username) {
            $session->send($this->packet_service->server_error('missing username or identifier'));
            return;
        }

        try {
            $player = $this->auth_service->login($session, $username, $identifier_str);
        } catch (\Throwable $e) {
            $session->send($this->packet_service->server_error($e->getMessage()));
            return;
        }
        
        $room = $this->multiplayer_service->get_player_room($player);
        $this->send($session, $this->packet_service->server_login(
            $player->get_username(),
            $player->get_player_id(),
            $room,
            $this->multiplayer_service->get_floor($room),
            $player->get_img() ?? $this->asset_service->get_player_default()
        ));
    }
}