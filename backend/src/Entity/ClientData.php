<?php

namespace App\Entity;

class ClientData {
    public ?Player $player;

    public function __construct() {
        $player = null;
    }
}