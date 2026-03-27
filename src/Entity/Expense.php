<?php

namespace App\Entity;

use App\Repository\ExpenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
class Expense
{
    public const CATEGORIES = [
        'Déplacements' => 'deplacements',
        'Impressions' => 'impressions',
        'Sous-traitance' => 'sous_traitance',
        'Logiciels' => 'logiciels',
        'Fournitures' => 'fournitures',
        'Divers' => 'divers',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'expenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 30)]
    private ?string $categorie = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private ?string $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $fournisseur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $justificatif = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): static { $this->date = $date; return $this; }

    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(string $categorie): static { $this->categorie = $categorie; return $this; }

    public function getCategorieLabel(): string
    {
        return array_search($this->categorie, self::CATEGORIES) ?: $this->categorie;
    }

    public function getMontant(): ?string { return $this->montant; }
    public function setMontant(string $montant): static { $this->montant = $montant; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getFournisseur(): ?string { return $this->fournisseur; }
    public function setFournisseur(?string $fournisseur): static { $this->fournisseur = $fournisseur; return $this; }

    public function getJustificatif(): ?string { return $this->justificatif; }
    public function setJustificatif(?string $justificatif): static { $this->justificatif = $justificatif; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
