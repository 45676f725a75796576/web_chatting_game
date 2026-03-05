<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\MultiplayerService;
use App\Service\PacketService;
use Psr\Log\LoggerInterface;

class RoomSkinController extends AbstractPacketController
{
    public function __construct(
        private MultiplayerService $multiplayer_service,
        private PacketService $packet_service,
        private LoggerInterface $logger,
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'room_skin';
    }

    public function handle(Session $session, array $packet): void
    {
        $this->logger->info('packet received', [
            'packet' => $packet,
        ]);
        if(!$session->data->player)
        {
            $session->send($this->packet_service->server_error('user is not authenticated'));
            return;
        }

        $url = $packet['url'];
        if(!$url) {
            $session->send($this->packet_service->server_error('missing url'));
            return;
        }

        $res_packet = $this->multiplayer_service->change_room_skin($session, $url);

        $session->send($this->packet_service->server_success());

        if($res_packet) {
            $session->send($res_packet);
        }
        
        return;
    }
}