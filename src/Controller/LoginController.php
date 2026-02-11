<?php

namespace App\Controller;

use App\Entity\Session;

class LoginController extends AbstractPacketController
{
    public function supports(string $type): bool
    {
        return $type === 'login';
    }

    public function handle(Session $session, array $packet): void
    {
        $identifier_str = $packet['identifier_str'] ?? null;

        if(!$identifier_str) {
            $this->send($session, [
                'type' => 'server_login',
                'state' => 'error',
                'message' => 'missing identifier_str',
            ]);
            return;
        }

        // TODO: get player from a repository


        // will be returned by a repository
        $username = 'test'; 
        $player_id = 0;

        $session->data->authenticated = true;

        $this->send($session, [
            'type' => 'server_login',
            'state' => 'success',
            'username' => $username,
            'player_id' => $player_id
        ]);
    }
}