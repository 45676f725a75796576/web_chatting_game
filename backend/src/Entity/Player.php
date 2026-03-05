<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "players")]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'player_id', type: "integer")]
    private int $player_id;

    #[ORM\Column(name: 'identifier_str', type: "string", length: 255, unique: true)]
    private string $identifier_str;

    #[ORM\Column(name: 'username', type: "string", length: 100)]
    private string $username;

    #[ORM\Column(name: 'img', type: "string", length: 255, nullable: true)]
    private ?string $img = null;
    
    #[ORM\Column(name: 'room_img', type: "string", length: 255, nullable: true)]
    private ?string $room_img = null;
    
    #[ORM\Column(name: 'locked', type: "boolean")]
    private bool $locked = false;
    
    #[ORM\Column(name: 'admin', type: "boolean")]
    private bool $admin = false;


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

    public function get_locked(): bool
    {
        return $this->locked;
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
    
    public function set_locked(bool $locked)
    {
        $this->locked = $locked;
    }

    public function get_admin()
    {
        return $this->admin;
    }
    
    public function set_admin(bool $admin)
    {
        $this->admin = $admin;
    }
}