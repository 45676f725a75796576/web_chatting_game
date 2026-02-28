<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\MultiplayerService;
use App\Service\AssetService;

class ChatController extends AbstractPacketController
{
    public function __construct(
        private PlayerRepository $player_repository,
        private MultiplayerService $multiplayer_service,
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'chat';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send([
                'type' => 'server_chat',
                'state' =>'error',
                'message' => 'user is not authenticated'
            ]);
            return;
        }

        $message = $packet['message'];
        if(!$message) {
            $session->send([
                'type' => 'server_chat',
                'state' =>'error',
                'message' => 'missing message field'
            ]);
            return;
        }

        $this->multiplayer_service->send_chat_message($session, $message);
    }
}