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
        if(!$session->data->player)
        {
            $session->send([
                'type' => 'server_enter_game',
                'state' =>'error',
                'message' => 'user is not authenticated'
            ]);
            return;
        }

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


            $packets = null;
            try {
                $packets = $this->multiplayer_service->join_room($session, $dest_player);
            } catch (\Throwable $e) {
                $this->send($session, [
                    'type' => 'server_place',
                    'state' => 'error',
                    'message' => $e->getMessage(),
                ]);
                return;
            }

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
            $packets = null;
            try {
                $packets = $this->multiplayer_service->join_floor($session, $dest_id);
            } catch (\Throwable $e) {
                $this->send($session, [
                    'type' => 'server_place',
                    'state' => 'error',
                    'message' => $e->getMessage(),
                ]);
                return;
            }
            
            $session->send([
                'type' => 'server_place',
                'place' => [
                    'img' => 'https://images.pexels.com/photos/5371570/pexels-photo-5371570.jpeg',
                    'id' => $dest_id,
                    'is_floor' => true 
                ]
            ]);

            foreach($packets as $p) {
                $session->send($p);
            }
        }
    }
}