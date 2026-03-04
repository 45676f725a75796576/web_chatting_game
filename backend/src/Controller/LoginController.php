<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\AuthService;
use App\Service\AssetService;
use App\Service\PacketService;
use Psr\Log\LoggerInterface;

class LoginController extends AbstractPacketController
{
    public function __construct(
        private AuthService $auth_service,
        private AssetService $asset_service,
        private PacketService $packet_service,
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
            $this->logger->error('Exception occurred', [
                'exception' => $e->getMessage(),
            ]);
            $session->send($this->packet_service->server_error('failed to login'));
            return;
        }
        
        if(!$player) {
            $session->send($this->packet_service->server_error('invalid username or password'));
            return;
        }

        $this->send($session, $this->packet_service->server_login(
            $player->get_username(),
            $player->get_player_id(),
            $player->get_img() ?? $this->asset_service->get_player_default()
        ));
    }
}