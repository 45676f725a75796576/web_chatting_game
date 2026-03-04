<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\AuthService;
use App\Service\AssetService;

class LoginController extends AbstractPacketController
{
    public function __construct(
        private AuthService $auth_service,
        private AssetService $asset_service
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
            $this->send($session, [
                'type' => 'server_login',
                'state' => 'error',
                'message' => 'missing username or identifier',
            ]);
            return;
        }

        $player = $this->auth_service->login($session, $username, $identifier_str);
        
        if(!$player) {
            $this->send($session, [
                'type' => 'server_login',
                'state' => 'error',
                'message' => 'invalid username or password',
            ]);
            return;
        }

        $this->send($session, [
            'type' => 'server_login',
            'state' => 'success',
            'username' => $player->get_username(),
            'player_id' => $player->get_player_id(),
            'img' => $player->get_img() ?? $this->asset_service->get_player_default()
        ]);
    }
}