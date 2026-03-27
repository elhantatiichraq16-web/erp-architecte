<?php

namespace App\Entity;

use App\Repository\TimeEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TimeEntryRepository::class)]
class TimeEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Collaborator::class, inversedBy: 'timeEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Collaborator $collaborator = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'timeEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $phase = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\Positive]
    private ?string $heures = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $facturable = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getCollaborator(): ?Collaborator { return $this->collaborator; }
    public function setCollaborator(?Collaborator $collaborator): static { $this->collaborator = $collaborator; return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getPhase(): ?string { return $this->phase; }
    public function setPhase(?string $phase): static { $this->phase = $phase; return $this; }

    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): static { $this->date = $date; return $this; }

    public function getHeures(): ?string { return $this->heures; }
    public function setHeures(string $heures): static { $this->heures = $heures; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function isFacturable(): bool { return $this->facturable; }
    public function setFacturable(bool $facturable): static { $this->facturable = $facturable; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getCout(): float
    {
        if (!$this->collaborator) return 0;
        return (float) $this->heures * (float) $this->collaborator->getTauxHoraire();
    }
}
