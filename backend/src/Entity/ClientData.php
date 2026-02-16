<?php

namespace App\Entity;

class ClientData {
    public ?Player $player;
    public ?int $floor;
    public ?int $room;

    public function __construct() {
        $this->player = null;
        $this->floor = null;
        $this->room = null;
    }
}