<?php

namespace App\Entity;

class ClientData {
    public ?Player $player;
    public ?int $room = null;
    public ?int $floor = null;
    public int $x = 0;
    public int $y = 0;

    public function __construct() {
        $this->player = null;
    }
}