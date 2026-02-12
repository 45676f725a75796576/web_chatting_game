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
    private int $playerId;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $identifierStr;

    #[ORM\Column(type: "string", length: 100)]
    private string $username;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $img = null;

    public function getPlayerId(): int
    {
        return $this->playerId;
    }

    public function getIdentifierStr(): string
    {
        return $this->identifierStr;
    }

    public function setIdentifierStr(string $identifierStr): self
    {
        $this->identifierStr = $identifierStr;
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