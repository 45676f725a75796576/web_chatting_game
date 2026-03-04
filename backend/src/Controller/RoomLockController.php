<?php

namespace App\Controller;

use App\Entity\Session;
use App\Service\MultiplayerService;
use App\Service\PacketService;
use Doctrine\ORM\EntityManagerInterface;

class RoomLockController extends AbstractPacketController
{
    public function __construct(
        private PacketService $packet_service,
        private EntityManagerInterface $em,
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'room_lock';
    }

    public function handle(Session $session, array $packet): void
    {
        if(!$session->data->player)
        {
            $session->send($this->packet_service->server_error('player is not authenticated'));
            return;
        }

        $lock = $packet['lock'];
        if(!$lock) {
            $session->send($this->packet_service->server_error('lock is missing'));
            return;
        }

        $session->data->player->set_locked(true);
        $this->em->flush();
    }
}