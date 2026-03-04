<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "utm")]
class Utm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 100)]
    private string $utm_source;

    #[ORM\Column(type: "string", length: 100)]
    private string $utm_campaign;

    #[ORM\Column(type: "string", length: 100)]
    private string $utm_medium;
    
    public function get_utm_source(): string
    {
        return $this->utm_source;
    }
    
    public function get_utm_medium(): string
    {
        return $this->utm_medium;
    }
    
    public function get_utm_campaign(): string
    {
        return $this->utm_campaign;
    }
    public function set_utm_source(string $utm_source)
    {
        $this->utm_source = $utm_source;
    }

    public function set_utm_medium(string $utm_medium)
    {
        $this->utm_medium = $utm_medium;
    }
    
    public function set_utm_campaign(string $utm_campaign)
    {
        $this->utm_campaign = $utm_campaign;
    }
    
    public function get_id(): int
    {
        return $this->id;
    }
}