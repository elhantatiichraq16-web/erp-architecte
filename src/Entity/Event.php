<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    public const TYPES = [
        'Réunion chantier' => 'reunion_chantier',
        'RDV client' => 'rdv_client',
        'Échéance' => 'echeance',
        'Livraison' => 'livraison',
        'Autre' => 'autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    private bool $allDay = false;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'events')]
    private ?Project $project = null;

    #[ORM\Column(length: 30)]
    private string $type = 'autre';

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    public function __construct()
    {
        $this->dateDebut = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function isAllDay(): bool { return $this->allDay; }
    public function setAllDay(bool $allDay): static { $this->allDay = $allDay; return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getTypeLabel(): string
    {
        return array_search($this->type, self::TYPES) ?: $this->type;
    }

    public function getCouleur(): ?string { return $this->couleur; }
    public function setCouleur(?string $couleur): static { $this->couleur = $couleur; return $this; }

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(?string $lieu): static { $this->lieu = $lieu; return $this; }

    public function toFullCalendarArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->titre,
            'start' => $this->dateDebut->format('c'),
            'end' => $this->dateFin?->format('c'),
            'allDay' => $this->allDay,
            'color' => $this->couleur ?? ($this->project?->getCouleur() ?? '#3B82F6'),
            'extendedProps' => [
                'type' => $this->type,
                'lieu' => $this->lieu,
                'description' => $this->description,
                'projectId' => $this->project?->getId(),
            ],
        ];
    }
}
