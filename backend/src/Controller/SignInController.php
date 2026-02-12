<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\AssetService;
use App\Service\AuthService;

class SignInController extends AbstractPacketController
{
    public function __construct(
        private AuthService $auth_service,
        private AssetService $asset_service
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'sign_in';
    }

    public function handle(Session $session, array $packet): void
    {
        $username = $packet['username'] ?? null;

        if(!$username) {
            $this->send($session, [
                'type' => 'server_sign_in',
                'state' => 'error',
                'message' => 'missing username',
            ]);
            return;
        }

        $player = null;
        try {
            $player = $this->auth_service->signin($session, $username);
        } catch (\Throwable $e) {
            $this->send($session, [
                'type' => 'server_sign_in',
                'state' => 'error',
                'message' => 'failed to sign in, the username already exists (or internal error :( )',
            ]);
        }

        $this->send($session, [
            'type' => 'server_sign_in',
            'state' => 'success',
            'identifier_str' => $player->getIdentifierStr(),
            'player_id' => $player->getPlayerId(),
            'img' => $player->getImg() ?? $this->asset_service->getPlayerDefault()
        ]);
    }

    function randomLetters(int $length): string
    {
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';

        $maxIndex = strlen($letters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $index = random_int(0, $maxIndex);
            $result .= $letters[$index];
        }

        return $result;
    }
}