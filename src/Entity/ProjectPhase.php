<?php

namespace App\Entity;

use App\Repository\ProjectPhaseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectPhaseRepository::class)]
class ProjectPhase
{
    public const PHASES = [
        'ESQ' => 'Esquisse',
        'APS' => 'Avant-Projet Sommaire',
        'APD' => 'Avant-Projet Définitif',
        'PRO' => 'Projet',
        'DCE' => 'Dossier de Consultation',
        'ACT' => 'Assistance Contrats Travaux',
        'VISA' => 'Visa',
        'DET' => 'Direction Exécution Travaux',
        'AOR' => 'Assistance Opérations Réception',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'phases')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(length: 10)]
    private ?string $phase = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $avancement = 0;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $ordre = 0;

    public function getId(): ?int { return $this->id; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getPhase(): ?string { return $this->phase; }
    public function setPhase(string $phase): static { $this->phase = $phase; return $this; }

    public function getPhaseLabel(): string
    {
        return self::PHASES[$this->phase] ?? $this->phase;
    }

    public function getAvancement(): int { return $this->avancement; }
    public function setAvancement(int $avancement): static { $this->avancement = max(0, min(100, $avancement)); return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getOrdre(): int { return $this->ordre; }
    public function setOrdre(int $ordre): static { $this->ordre = $ordre; return $this; }

    public function getProgressBarClass(): string
    {
        if ($this->avancement >= 100) return 'bg-success';
        if ($this->avancement >= 50) return 'bg-primary';
        if ($this->avancement >= 25) return 'bg-info';
        return 'bg-warning';
    }

    public function __toString(): string
    {
        return $this->getPhaseLabel() . ' (' . $this->avancement . '%)';
    }
}
