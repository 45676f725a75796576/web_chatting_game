<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="players")
 */
class Player
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $player_id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private string $identifier_str;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $img = null;

    // Getters and setters

    public function getPlayerId(): int
    {
        return $this->player_id;
    }

    public function getIdentifierStr(): string
    {
        return $this->identifier_str;
    }

    public function setIdentifierStr(string $identifier_str): self
    {
        $this->identifier_str = $identifier_str;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): self
    {
        $this->img = $img;
        return $this;
    }
}