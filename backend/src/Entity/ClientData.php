<?php

namespace App\Entity;

class ClientData {
    public bool $authenticated;

    //public Player $player;

    public function __construct() {
        $this->authenticated = false;
    }
}