<?php

namespace App\Controller;

use App\Entity\Session;

class SignInController extends AbstractPacketController
{
    public function supports(string $type): bool
    {
        return $type === 'sign_in';
    }

    public function handle(Session $session, array $packet): void
    {
        $username = $packet['username'] ?? null;

        if(!$username) {
            $this->send($session, [
                'type' => 'server_sign_in',
                'state' => 'error',
                'message' => 'missing username',
            ]);
            return;
        }

        $identifier_str = $this->randomLetters(5);
        $player_id = 0; // will be returned by a repository

        // TODO: call the player repository

        $session->data->authenticated = true;
        
        $this->send($session, [
            'type' => 'server_sign_in',
            'state' => 'success',
            'identifier_str' => $identifier_str,
            'player_id' => $player_id
        ]);
    }

    function randomLetters(int $length): string
    {
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';

        $maxIndex = strlen($letters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $index = random_int(0, $maxIndex);
            $result .= $letters[$index];
        }

        return $result;
    }
}