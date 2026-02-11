<?php

namespace App\Model;

class Player
{
    private int $id;
    private string $username;

    private float $x = 0;
    private float $y = 0;

    public function __construct(int $id, string $username)
    {
        $this->id = $id;
        $this->username = $username;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function setPosition(float $x, float $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}
