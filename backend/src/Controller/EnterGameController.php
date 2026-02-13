<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\MultiplayerService;

class EnterGameController extends AbstractPacketController
{
    public function __construct(
        private MultiplayerService $multiplayer_service
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
            
        $session->send([
            'type' => 'server_enter_game',
            'state' =>'success',
        ]);
    }
}