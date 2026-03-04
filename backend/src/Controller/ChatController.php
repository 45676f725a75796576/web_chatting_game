<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\MultiplayerService;
use App\Service\AssetService;
use App\Service\PacketService;

class ChatController extends AbstractPacketController
{
    public function __construct(
        private PlayerRepository $player_repository,
        private MultiplayerService $multiplayer_service,
        private PacketService $packet_service,
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'chat';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send($this->packet_service->server_error('user is not authenticated'));
            return;
        }

        $message = $packet['message'];
        if(!$message) {
            $session->send($this->packet_service->server_error('missing message field'));
            return;
        }

        $res_packet = $this->multiplayer_service->send_chat_message($session, $message);
        if($res_packet) {
            $session->send($res_packet);
        } 
    }
}