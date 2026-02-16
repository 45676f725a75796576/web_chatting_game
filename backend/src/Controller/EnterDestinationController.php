<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\MultiplayerService;
use App\Service\AssetService;

class EnterDestinationController extends AbstractPacketController
{
    public function __construct(
        private PlayerRepository $player_repository,
        private MultiplayerService $multiplayer_service,
        private AssetService $asset_service
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'enter_destination';
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

        $player = $session->data->player;
        $dest_id = $packet['dest_id'];
        $is_floor = $packet['is_floor'];

        if($is_floor == '0') {
            $dest_player = $this->player_repository->findById($dest_id);
            if(!$dest_player) {
                $session->send([
                    'type' => 'server_enter_game',
                    'state' =>'error',
                    'message' => 'room not found'
                ]);
            }

            $packets = $this->multiplayer_service->join_room($session, $dest_player);

            $session->send([
                'type' => 'server_place',
                'place' => [
                    'img' => $dest_player->getRoomImg() ?? $this->asset_service->getRoomDefault(),
                    'id' => $dest_player->getPlayerId(),
                    'is_floor' => false
                ]
            ]);

            foreach($packets as $p) {
                $session->send($p);
            }

        } else if($is_floor == '1') {
            $packets = $this->multiplayer_service->join_floor($session, $dest_id);
            foreach($packets as $p) {
                $session->send($p);
            }

        }
    }
}