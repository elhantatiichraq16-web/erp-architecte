<?php

namespace App\Entity;

use App\Repository\InvoiceLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceLineRepository::class)]
class InvoiceLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Invoice $invoice = null;

    #[ORM\Column(length: 255)]
    private ?string $designation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $quantite = '1.00';

    #[ORM\Column(length: 20)]
    private string $unite = 'forfait';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $prixUnitaireHT = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $montantHT = '0.00';

    #[ORM\Column(type: Types::INTEGER)]
    private int $ordre = 0;

    public function getId(): ?int { return $this->id; }

    public function getInvoice(): ?Invoice { return $this->invoice; }
    public function setInvoice(?Invoice $invoice): static { $this->invoice = $invoice; return $this; }

    public function getDesignation(): ?string { return $this->designation; }
    public function setDesignation(string $designation): static { $this->designation = $designation; return $this; }

    public function getQuantite(): string { return $this->quantite; }
    public function setQuantite(?string $quantite): static { $this->quantite = $quantite ?? '1.00'; return $this; }

    public function getUnite(): string { return $this->unite; }
    public function setUnite(?string $unite): static { $this->unite = $unite ?? 'forfait'; return $this; }

    public function getPrixUnitaireHT(): string { return $this->prixUnitaireHT; }
    public function setPrixUnitaireHT(?string $prixUnitaireHT): static { $this->prixUnitaireHT = $prixUnitaireHT ?? '0.00'; return $this; }

    public function getMontantHT(): string { return $this->montantHT; }
    public function setMontantHT(string $montantHT): static { $this->montantHT = $montantHT; return $this; }

    public function getOrdre(): int { return $this->ordre; }
    public function setOrdre(?int $ordre): static { $this->ordre = $ordre ?? 0; return $this; }

    public function calculateMontant(): void
    {
        $this->montantHT = number_format((float) $this->quantite * (float) $this->prixUnitaireHT, 2, '.', '');
    }
}
