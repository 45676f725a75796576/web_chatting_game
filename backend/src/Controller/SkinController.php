<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlayerRepository;
use App\Service\PacketService;
use App\Service\MultiplayerService;
use App\Service\AssetService;
use Psr\Log\LoggerInterface;

class SkinController extends AbstractPacketController
{
    public function __construct(
        private MultiplayerService $multiplayer_service,
        private LoggerInterface $logger,
        private PacketService $packet_service
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'skin';
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

        $res_packet = $this->multiplayer_service->change_player_skin($session, $url);

        $session->send($this->packet_service->server_success());
        
        if($res_packet) {
            $session->send($res_packet);
        }
        return;
    }
}