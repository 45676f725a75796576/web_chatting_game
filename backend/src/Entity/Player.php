<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "players")]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $player_id;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $identifier_str;

    #[ORM\Column(type: "string", length: 100)]
    private string $username;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $img = null;
    
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $room_img = null;

    public function get_player_id(): int
    {
        return $this->player_id;
    }

    public function get_identifier_str(): string
    {
        return $this->identifier_str;
    }

    public function set_identifier_str(string $identifier_str): self
    {
        $this->identifier_str = $identifier_str;
        return $this;
    }

    public function get_username(): string
    {
        return $this->username;
    }

    public function set_username(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function get_img(): ?string
    {
        return $this->img;
    }

    public function set_img(?string $img): self
    {
        $this->img = $img;
        return $this;
    }

    public function get_room_img(): ?string
    {
        return $this->room_img;
    }

    public function set_room_img(?string $img): self
    {
        $this->room_img = $img;
        return $this;
    }
}