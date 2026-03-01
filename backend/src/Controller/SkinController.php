<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\MultiplayerService;
use App\Service\AssetService;

class SkinController extends AbstractPacketController
{
    public function __construct(
        private PlayerRepository $player_repository,
        private MultiplayerService $multiplayer_service,
        private AssetService $asset_service
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'skin';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send([
                'type' => 'server_skin',
                'state' =>'error',
                'message' => 'user is not authenticated'
            ]);
            return;
        }

        $url = $packet['url'];

        $this->multiplayer_service->change_player_skin($session, $url);

        $session->send([
            'type' => 'server_skin',
            'state' =>'success',
        ]);
        return;
    }
}