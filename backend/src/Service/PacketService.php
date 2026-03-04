<?php

namespace App\Service;

class PacketService
{
    public function server_sign_in(string $identifier, int $player_id, int $room_id, string $img_url): array {
        return [
            'type' => 'server_sign_in',
            'state' => 'success',
            'identifier_str' => $identifier,
            'player_id' => $player_id,
            'room_id' => $room_id,
            'img' => $img_url
        ];
    }
    

    public function server_login(string $username, int $player_id, int $room_id, string $img_url): array {
        return [
            'type' => 'server_login',
            'state' => 'success',
            'player_id' => $player_id,
            'room_id' => $room_id,
            'username' => $username,
            'img' => $img_url
        ];
    }

    public function server_room(string $img_url, int $room_id, string $username, int $floor): array {
        return [
            'type' => 'server_room',
            'state' => 'success',
            'img' => $img_url,
            'room_id' => $room_id,
            'username' => $username,
            'floor' => $floor
        ];
    }

    public function server_floor(string $img_url, int $floor_id, array $rooms): array {
        return [
            'type' => 'server_floor',
            'state' => 'success',
            'img' => $img_url,
            'floor_id' => $floor_id,
            'rooms' => $rooms
        ];
    }

    public function server_player_pos(int $player_id, int $x, int $y, bool $flip): array {
        return [
            'type' => 'server_player_pos',
            'player_id' => $player_id,
            'pos' => ['x' => $x, 'y' => $y],
            'flip' => $flip
        ];
    }

    public function server_disconnect(int $player_id): array {
        return [
            'type' => 'server_disconnect',
            'player_id' => $player_id
        ];
    }

    public function server_chat(int $player_id, string $message, int $timeout): array {
        return [
            'type' => 'server_chat',
            'state' => 'success',
            'player_id' => $player_id,
            'message' => $message,
            'timeout' => $timeout
        ];
    }

    public function server_skin_update(int $player_id, string $url): array {
        return [
            'type' => 'server_skin_update',
            'player_id' => $player_id,
            'url' => $url
        ];
    }

    public function server_room_skin_update(string $url): array {
        return [
            'type' => 'server_room_skin_update',
            'url' => $url
        ];
    }

    public function server_success(): array {
        return [
            'type' => 'server_success',
            'state' => 'success'
        ];

    }

    public function server_error(string $message) {
        return [
            'type' => 'server_error',
            'state' => 'error',
            'message' => $message
        ];
    }

    public function server_new_player(int $player_id, string $username, string $img, int $x, int $y) {
        return [
            'type' => 'server_new_player',
            'player_id' => $player_id,
            'username' => $username,
            'img' => $img,
            'pos' => [
                'x' => $x,
                'y' => $y,
            ]
        ];
    }
}