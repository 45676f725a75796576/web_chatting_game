<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\UtmRepository;
use Psr\Log\LoggerInterface;

class UtmController extends AbstractPacketController
{
    public function __construct(
        private UtmRepository $utm_repository,
        private LoggerInterface $logger,
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'utm';
    }

    public function handle(Session $session, array $packet): void
    {
        $this->logger->info('packet received', [
            'packet' => $packet,
        ]);
        $utm_source = $packet['utm_source'];
        $utm_medium = $packet['utm_source'];
        $utm_campaign = $packet['utm_campaign'];

        if(!$utm_source || !$utm_medium || !$utm_campaign) {
            return;
        }

        $this->utm_repository->insert_utm(
            $utm_source,
            $utm_medium,
            $utm_campaign
        );

        $session->send([
            'type' => 'server_utm',
            'state' => 'success'
        ]);

        return;
    }
}