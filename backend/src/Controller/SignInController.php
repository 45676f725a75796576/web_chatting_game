<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\AssetService;
use App\Service\PacketService;
use App\Service\AuthService;
use App\Service\MultiplayerService;
use Psr\Log\LoggerInterface;

class SignInController extends AbstractPacketController
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
        return $type === 'sign_in';
    }

    public function handle(Session $session, array $packet): void
    {
        $this->logger->info('packet received', [
            'packet' => $packet,
        ]);
        $username = $packet['username'] ?? null;

        if(!$username) {
            $session->send($this->packet_service->server_error('missing username'));
            return;
        }

        $player = null;
        try {
            $player = $this->auth_service->signin($session, $username);
        } catch (\Throwable $e) {
            $session->send($this->packet_service->server_error($e->getMessage()));
            return;
        }

        $room = $this->multiplayer_service->get_player_room($player);
        $session->send($this->packet_service->server_sign_in(
            $player->get_identifier_str(),
            $player->get_player_id(),
            $room,
            $this->multiplayer_service->get_floor($room),
            $player->get_img() ?? $this->asset_service->get_player_default(),
        ));
    }

    function random_letters(int $length): string
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