<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
class Invoice
{
    public const STATUS_EN_ATTENTE = 'en_attente';
    public const STATUS_PAYEE = 'payee';
    public const STATUS_EN_RETARD = 'en_retard';

    public const STATUSES = [
        'En attente' => self::STATUS_EN_ATTENTE,
        'Payée' => self::STATUS_PAYEE,
        'En retard' => self::STATUS_EN_RETARD,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $numero = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Quote::class)]
    private ?Quote $quote = null;

    #[ORM\Column(length: 255)]
    private ?string $objet = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateEmission = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEcheance = null;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUS_EN_ATTENTE;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalHT = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalTVA = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalTTC = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $tauxTVA = '20.00';

    /** @var Collection<int, InvoiceLine> */
    #[ORM\OneToMany(targetEntity: InvoiceLine::class, mappedBy: 'invoice', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->dateEmission = new \DateTime();
        $this->dateEcheance = (new \DateTime())->modify('+30 days');
    }

    public function getId(): ?int { return $this->id; }

    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(string $numero): static { $this->numero = $numero; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getQuote(): ?Quote { return $this->quote; }
    public function setQuote(?Quote $quote): static { $this->quote = $quote; return $this; }

    public function getObjet(): ?string { return $this->objet; }
    public function setObjet(string $objet): static { $this->objet = $objet; return $this; }

    public function getDateEmission(): ?\DateTimeInterface { return $this->dateEmission; }
    public function setDateEmission(\DateTimeInterface $dateEmission): static { $this->dateEmission = $dateEmission; return $this; }

    public function getDateEcheance(): ?\DateTimeInterface { return $this->dateEcheance; }
    public function setDateEcheance(?\DateTimeInterface $dateEcheance): static { $this->dateEcheance = $dateEcheance; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getDatePaiement(): ?\DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(?\DateTimeInterface $datePaiement): static { $this->datePaiement = $datePaiement; return $this; }

    public function getTotalHT(): string { return $this->totalHT; }
    public function setTotalHT(string $totalHT): static { $this->totalHT = $totalHT; return $this; }

    public function getTotalTVA(): string { return $this->totalTVA; }
    public function setTotalTVA(string $totalTVA): static { $this->totalTVA = $totalTVA; return $this; }

    public function getTotalTTC(): string { return $this->totalTTC; }
    public function setTotalTTC(string $totalTTC): static { $this->totalTTC = $totalTTC; return $this; }

    public function getTauxTVA(): string { return $this->tauxTVA; }
    public function setTauxTVA(string $tauxTVA): static { $this->tauxTVA = $tauxTVA; return $this; }

    /** @return Collection<int, InvoiceLine> */
    public function getLines(): Collection { return $this->lines; }
    public function addLine(InvoiceLine $line): static { if (!$this->lines->contains($line)) { $this->lines->add($line); $line->setInvoice($this); } return $this; }
    public function removeLine(InvoiceLine $line): static { if ($this->lines->removeElement($line)) { if ($line->getInvoice() === $this) { $line->setInvoice(null); } } return $this; }

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

    public function isEnRetard(): bool
    {
        return $this->statut !== self::STATUS_PAYEE
            && $this->dateEcheance !== null
            && $this->dateEcheance < new \DateTime();
    }

    public function getStatutLabel(): string
    {
        return match($this->statut) {
            self::STATUS_EN_ATTENTE => 'En attente',
            self::STATUS_PAYEE => 'Payée',
            self::STATUS_EN_RETARD => 'En retard',
            default => $this->statut,
        };
    }

    public function getStatutBadgeClass(): string
    {
        return match($this->statut) {
            self::STATUS_EN_ATTENTE => 'bg-warning',
            self::STATUS_PAYEE => 'bg-success',
            self::STATUS_EN_RETARD => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function __toString(): string
    {
        return $this->numero . ' - ' . $this->objet;
    }
}
