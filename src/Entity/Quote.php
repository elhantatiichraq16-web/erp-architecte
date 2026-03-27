<?php

namespace App\Entity;

use App\Repository\QuoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\Table(name: '`quote`')]
class Quote
{
    public const STATUS_BROUILLON = 'brouillon';
    public const STATUS_ENVOYE = 'envoye';
    public const STATUS_ACCEPTE = 'accepte';
    public const STATUS_REFUSE = 'refuse';

    public const STATUSES = [
        'Brouillon' => self::STATUS_BROUILLON,
        'Envoyé' => self::STATUS_ENVOYE,
        'Accepté' => self::STATUS_ACCEPTE,
        'Refusé' => self::STATUS_REFUSE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $numero = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    private ?Project $project = null;

    #[ORM\Column(length: 255)]
    private ?string $objet = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidite = null;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUS_BROUILLON;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalHT = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalTVA = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalTTC = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $tauxTVA = '20.00';

    /** @var Collection<int, QuoteLine> */
    #[ORM\OneToMany(targetEntity: QuoteLine::class, mappedBy: 'quote', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->dateValidite = (new \DateTime())->modify('+30 days');
    }

    public function getId(): ?int { return $this->id; }

    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(string $numero): static { $this->numero = $numero; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getObjet(): ?string { return $this->objet; }
    public function setObjet(string $objet): static { $this->objet = $objet; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function getDateValidite(): ?\DateTimeInterface { return $this->dateValidite; }
    public function setDateValidite(?\DateTimeInterface $dateValidite): static { $this->dateValidite = $dateValidite; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getTotalHT(): string { return $this->totalHT; }
    public function setTotalHT(string $totalHT): static { $this->totalHT = $totalHT; return $this; }

    public function getTotalTVA(): string { return $this->totalTVA; }
    public function setTotalTVA(string $totalTVA): static { $this->totalTVA = $totalTVA; return $this; }

    public function getTotalTTC(): string { return $this->totalTTC; }
    public function setTotalTTC(string $totalTTC): static { $this->totalTTC = $totalTTC; return $this; }

    public function getTauxTVA(): string { return $this->tauxTVA; }
    public function setTauxTVA(string $tauxTVA): static { $this->tauxTVA = $tauxTVA; return $this; }

    /** @return Collection<int, QuoteLine> */
    public function getLines(): Collection { return $this->lines; }
    public function addLine(QuoteLine $line): static { if (!$this->lines->contains($line)) { $this->lines->add($line); $line->setQuote($this); } return $this; }
    public function removeLine(QuoteLine $line): static { if ($this->lines->removeElement($line)) { if ($line->getQuote() === $this) { $line->setQuote(null); } } return $this; }

    public function calculateTotals(): void
    {
        $ht = 0;
        foreach ($this->lines as $line) {
            $line->calculateMontant();
            $ht += (float) $line->getMontantHT();
        }
        $this->totalHT = number_format($ht, 2, '.', '');
        $tva = $ht * (float) $this->tauxTVA / 100;
        $this->totalTVA = number_format($tva, 2, '.', '');
        $this->totalTTC = number_format($ht + $tva, 2, '.', '');
    }

    public function getStatutLabel(): string
    {
        return match($this->statut) {
            self::STATUS_BROUILLON => 'Brouillon',
            self::STATUS_ENVOYE => 'Envoyé',
            self::STATUS_ACCEPTE => 'Accepté',
            self::STATUS_REFUSE => 'Refusé',
            default => $this->statut,
        };
    }

    public function getStatutBadgeClass(): string
    {
        return match($this->statut) {
            self::STATUS_BROUILLON => 'bg-secondary',
            self::STATUS_ENVOYE => 'bg-info',
            self::STATUS_ACCEPTE => 'bg-success',
            self::STATUS_REFUSE => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function __toString(): string
    {
        return $this->numero . ' - ' . $this->objet;
    }
}
