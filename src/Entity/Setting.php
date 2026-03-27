<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $cle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $valeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int { return $this->id; }

    public function getCle(): ?string { return $this->cle; }
    public function setCle(string $cle): static { $this->cle = $cle; return $this; }

    public function getValeur(): ?string { return $this->valeur; }
    public function setValeur(?string $valeur): static { $this->valeur = $valeur; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
}
