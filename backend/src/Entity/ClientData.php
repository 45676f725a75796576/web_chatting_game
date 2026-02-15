<?php

namespace App\Entity;

class ClientData {
    public ?Player $player;

    public function __construct() {
        $this->player = null;
    }
}