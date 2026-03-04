<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\MultiplayerService;

class PlayerPosController extends AbstractPacketController
{
    public function __construct(
        private MultiplayerService $multiplayer_service,
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'player_pos';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            return;
        }

        $x = $packet['pos']['x'];
        $y = $packet['pos']['y'];
        $flip = $packet['pos']['flip'];

        $this->multiplayer_service->update_player_pos($session, $x, $y, $flip);
    }
}