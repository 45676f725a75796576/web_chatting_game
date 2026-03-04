<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\MultiplayerService;
use App\Service\AssetService;

class RoomSkinController extends AbstractPacketController
{
    public function __construct(
        private MultiplayerService $multiplayer_service,
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'room_skin';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send([
                'type' => 'server_room_skin',
                'state' =>'error',
                'message' => 'user is not authenticated'
            ]);
            return;
        }

        $url = $packet['url'];
        if(!$url) {
            $session->send([
                'type' => 'server_room_skin',
                'state' =>'error',
                'message' => 'missing url'
            ]);
            return;
        }

        $res_packet = $this->multiplayer_service->change_room_skin($session, $url);

        $session->send([
            'type' => 'server_room_skin',
            'state' =>'success',
        ]);

        if($res_packet) {
            $session->send($res_packet);
        }
        return;
    }
}