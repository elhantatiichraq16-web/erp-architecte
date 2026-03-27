<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    public const STATUS_EN_ATTENTE = 'en_attente';
    public const STATUS_EN_COURS = 'en_cours';
    public const STATUS_TERMINE = 'termine';
    public const STATUS_SUSPENDU = 'suspendu';

    public const STATUSES = [
        'En attente' => self::STATUS_EN_ATTENTE,
        'En cours' => self::STATUS_EN_COURS,
        'Terminé' => self::STATUS_TERMINE,
        'Suspendu' => self::STATUS_SUSPENDU,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseChantier = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $surface = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $montantHonoraires = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $budgetPrevisionnel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinPrevisionnelle = null;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUS_EN_ATTENTE;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $couleur = '#3B82F6';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /** @var Collection<int, ProjectPhase> */
    #[ORM\OneToMany(targetEntity: ProjectPhase::class, mappedBy: 'project', orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $phases;

    /** @var Collection<int, TimeEntry> */
    #[ORM\OneToMany(targetEntity: TimeEntry::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $timeEntries;

    /** @var Collection<int, Expense> */
    #[ORM\OneToMany(targetEntity: Expense::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $expenses;

    /** @var Collection<int, Document> */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $documents;

    /** @var Collection<int, Event> */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'project')]
    private Collection $events;

    /** @var Collection<int, Collaborator> */
    #[ORM\ManyToMany(targetEntity: Collaborator::class, inversedBy: 'projects')]
    private Collection $collaborators;

    public function __construct()
    {
        $this->phases = new ArrayCollection();
        $this->timeEntries = new ArrayCollection();
        $this->expenses = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->collaborators = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(string $reference): static { $this->reference = $reference; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getAdresseChantier(): ?string { return $this->adresseChantier; }
    public function setAdresseChantier(?string $adresseChantier): static { $this->adresseChantier = $adresseChantier; return $this; }

    public function getSurface(): ?string { return $this->surface; }
    public function setSurface(?string $surface): static { $this->surface = $surface; return $this; }

    public function getMontantHonoraires(): ?string { return $this->montantHonoraires; }
    public function setMontantHonoraires(?string $montantHonoraires): static { $this->montantHonoraires = $montantHonoraires; return $this; }

    public function getBudgetPrevisionnel(): ?string { return $this->budgetPrevisionnel; }
    public function setBudgetPrevisionnel(?string $budgetPrevisionnel): static { $this->budgetPrevisionnel = $budgetPrevisionnel; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFinPrevisionnelle(): ?\DateTimeInterface { return $this->dateFinPrevisionnelle; }
    public function setDateFinPrevisionnelle(?\DateTimeInterface $dateFinPrevisionnelle): static { $this->dateFinPrevisionnelle = $dateFinPrevisionnelle; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getCouleur(): ?string { return $this->couleur; }
    public function setCouleur(?string $couleur): static { $this->couleur = $couleur; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    /** @return Collection<int, ProjectPhase> */
    public function getPhases(): Collection { return $this->phases; }
    public function addPhase(ProjectPhase $phase): static { if (!$this->phases->contains($phase)) { $this->phases->add($phase); $phase->setProject($this); } return $this; }
    public function removePhase(ProjectPhase $phase): static { if ($this->phases->removeElement($phase)) { if ($phase->getProject() === $this) { $phase->setProject(null); } } return $this; }

    /** @return Collection<int, TimeEntry> */
    public function getTimeEntries(): Collection { return $this->timeEntries; }

    /** @return Collection<int, Expense> */
    public function getExpenses(): Collection { return $this->expenses; }

    /** @return Collection<int, Document> */
    public function getDocuments(): Collection { return $this->documents; }

    /** @return Collection<int, Event> */
    public function getEvents(): Collection { return $this->events; }

    /** @return Collection<int, Collaborator> */
    public function getCollaborators(): Collection { return $this->collaborators; }
    public function addCollaborator(Collaborator $collaborator): static { if (!$this->collaborators->contains($collaborator)) { $this->collaborators->add($collaborator); } return $this; }
    public function removeCollaborator(Collaborator $collaborator): static { $this->collaborators->removeElement($collaborator); return $this; }

    public function getAvancementGlobal(): float
    {
        if ($this->phases->isEmpty()) return 0;
        $total = 0;
        foreach ($this->phases as $phase) {
            $total += $phase->getAvancement();
        }
        return round($total / $this->phases->count(), 1);
    }

    public function getTotalDepenses(): float
    {
        $total = 0;
        foreach ($this->expenses as $expense) {
            $total += (float) $expense->getMontant();
        }
        return $total;
    }

    public function getTotalHeures(): float
    {
        $total = 0;
        foreach ($this->timeEntries as $entry) {
            $total += (float) $entry->getHeures();
        }
        return $total;
    }

    public function getStatutLabel(): string
    {
        return match($this->statut) {
            self::STATUS_EN_ATTENTE => 'En attente',
            self::STATUS_EN_COURS => 'En cours',
            self::STATUS_TERMINE => 'Terminé',
            self::STATUS_SUSPENDU => 'Suspendu',
            default => $this->statut,
        };
    }

    public function getStatutBadgeClass(): string
    {
        return match($this->statut) {
            self::STATUS_EN_ATTENTE => 'bg-warning',
            self::STATUS_EN_COURS => 'bg-primary',
            self::STATUS_TERMINE => 'bg-success',
            self::STATUS_SUSPENDU => 'bg-secondary',
            default => 'bg-info',
        };
    }

    public function __toString(): string
    {
        return $this->reference . ' - ' . $this->nom;
    }
}
