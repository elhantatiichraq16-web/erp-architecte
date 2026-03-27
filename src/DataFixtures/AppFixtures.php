<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Collaborator;
use App\Entity\Document;
use App\Entity\Event;
use App\Entity\Expense;
use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use App\Entity\Project;
use App\Entity\ProjectPhase;
use App\Entity\Quote;
use App\Entity\QuoteLine;
use App\Entity\Setting;
use App\Entity\TimeEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // =====================================================================
        // SETTINGS
        // =====================================================================
        $settings = [
            ['cabinet_nom',           'Cabinet Architecture Mercier & Associés',  'Nom du cabinet'],
            ['cabinet_adresse',       '15 rue des Arts, 33000 Bordeaux',           'Adresse du cabinet'],
            ['cabinet_telephone',     '05 56 12 34 56',                            'Téléphone du cabinet'],
            ['cabinet_email',         'contact@mercier-archi.fr',                  'Email du cabinet'],
            ['cabinet_siret',         '12345678900012',                             'SIRET du cabinet'],
            ['tva_taux',              '20',                                         'Taux de TVA (%)'],
            ['devise',                'EUR',                                        'Devise'],
            ['numero_devis_prefix',   'DEV',                                        'Préfixe numéro devis'],
            ['numero_facture_prefix', 'FAC',                                        'Préfixe numéro facture'],
        ];

        foreach ($settings as [$cle, $valeur, $description]) {
            $setting = new Setting();
            $setting->setCle($cle);
            $setting->setValeur($valeur);
            $setting->setDescription($description);
            $manager->persist($setting);
        }

        // =====================================================================
        // CLIENTS
        // =====================================================================

        // Client 1 – SCI Les Jardins
        $clientSCI = new Client();
        $clientSCI->setNom('Les Jardins')
            ->setPrenom(null)
            ->setSociete('SCI Les Jardins')
            ->setAdresse('42 avenue des Platanes')
            ->setCodePostal('33700')
            ->setVille('Mérignac')
            ->setTelephone('05 56 34 78 90')
            ->setEmail('contact@sci-jardins.fr')
            ->setSiret('45678901200034')
            ->setNotes('Contact principal : Laurent Moreau. Promoteur immobilier résidentiel. Projet de maison individuelle sur terrain viabilisé.');
        $manager->persist($clientSCI);

        // Client 2 – Mairie de Bordeaux
        $clientMairie = new Client();
        $clientMairie->setNom('de Bordeaux')
            ->setPrenom(null)
            ->setSociete('Mairie de Bordeaux')
            ->setAdresse('Place Pey-Berland')
            ->setCodePostal('33000')
            ->setVille('Bordeaux')
            ->setTelephone('05 56 10 20 30')
            ->setEmail('s.legrand@mairie-bordeaux.fr')
            ->setSiret('21330063500019')
            ->setNotes('Contact : Sophie Legrand, Direction des affaires culturelles. Marché public de travaux pour rénovation médiathèque.');
        $manager->persist($clientMairie);

        // Client 3 – M. Jean Dupont
        $clientDupont = new Client();
        $clientDupont->setNom('Dupont')
            ->setPrenom('Jean')
            ->setSociete(null)
            ->setAdresse('8 impasse des Roses')
            ->setCodePostal('33400')
            ->setVille('Talence')
            ->setTelephone('06 12 45 67 89')
            ->setEmail('jean.dupont@gmail.com')
            ->setSiret(null)
            ->setNotes('Particulier. Extension de maison individuelle. Budget contraint, délais importants.');
        $manager->persist($clientDupont);

        // Client 4 – SARL Immo+
        $clientImmo = new Client();
        $clientImmo->setNom('Immo+')
            ->setPrenom(null)
            ->setSociete('SARL Immo+')
            ->setAdresse('75 cours de l\'Intendance')
            ->setCodePostal('33000')
            ->setVille('Bordeaux')
            ->setTelephone('05 57 89 01 23')
            ->setEmail('p.blanc@immoplus.fr')
            ->setSiret('78901234500056')
            ->setNotes('Contact : Pierre Blanc, gérant. Société d\'investissement immobilier. Deux projets en cours : aménagement bureaux et réhabilitation entrepôt.');
        $manager->persist($clientImmo);

        // Client 5 – Mme Claire Martin
        $clientMartin = new Client();
        $clientMartin->setNom('Martin')
            ->setPrenom('Claire')
            ->setSociete(null)
            ->setAdresse('22 allée des Cèdres')
            ->setCodePostal('33270')
            ->setVille('Floirac')
            ->setTelephone('06 78 90 12 34')
            ->setEmail('claire.martin@outlook.fr')
            ->setSiret(null)
            ->setNotes('Particulière. Construction d\'une villa contemporaine. Cliente exigeante sur la qualité architecturale et les matériaux.');
        $manager->persist($clientMartin);

        // =====================================================================
        // COLLABORATORS
        // =====================================================================

        $collabMercier = new Collaborator();
        $collabMercier->setNom('Mercier')
            ->setPrenom('Thomas')
            ->setEmail('t.mercier@mercier-archi.fr')
            ->setRole('Architecte DPLG')
            ->setTauxHoraire('85.00')
            ->setTelephone('06 11 22 33 44')
            ->setCouleur('#3B82F6')
            ->setActif(true);
        $manager->persist($collabMercier);

        $collabFontaine = new Collaborator();
        $collabFontaine->setNom('Fontaine')
            ->setPrenom('Julie')
            ->setEmail('j.fontaine@mercier-archi.fr')
            ->setRole('Architecte d\'intérieur')
            ->setTauxHoraire('75.00')
            ->setTelephone('06 22 33 44 55')
            ->setCouleur('#8B5CF6')
            ->setActif(true);
        $manager->persist($collabFontaine);

        $collabDubois = new Collaborator();
        $collabDubois->setNom('Dubois')
            ->setPrenom('Marc')
            ->setEmail('m.dubois@mercier-archi.fr')
            ->setRole('Dessinateur projeteur')
            ->setTauxHoraire('55.00')
            ->setTelephone('06 33 44 55 66')
            ->setCouleur('#10B981')
            ->setActif(true);
        $manager->persist($collabDubois);

        $collabPetit = new Collaborator();
        $collabPetit->setNom('Petit')
            ->setPrenom('Anne')
            ->setEmail('a.petit@mercier-archi.fr')
            ->setRole('Ingénieur structure')
            ->setTauxHoraire('90.00')
            ->setTelephone('06 44 55 66 77')
            ->setCouleur('#F59E0B')
            ->setActif(true);
        $manager->persist($collabPetit);

        $collabBernard = new Collaborator();
        $collabBernard->setNom('Bernard')
            ->setPrenom('Lucas')
            ->setEmail('l.bernard@mercier-archi.fr')
            ->setRole('Assistant projet')
            ->setTauxHoraire('45.00')
            ->setTelephone('06 55 66 77 88')
            ->setCouleur('#EF4444')
            ->setActif(true);
        $manager->persist($collabBernard);

        // =====================================================================
        // PROJECTS
        // =====================================================================

        // Project 1 – Maison individuelle Les Jardins
        $proj1 = new Project();
        $proj1->setReference('PROJ-2026-001')
            ->setNom('Maison individuelle Les Jardins')
            ->setClient($clientSCI)
            ->setAdresseChantier('Lotissement Les Jardins, parcelle B7, 33700 Mérignac')
            ->setSurface('180.00')
            ->setMontantHonoraires('45000.00')
            ->setBudgetPrevisionnel('350000.00')
            ->setDateDebut(new \DateTime('2025-09-01'))
            ->setDateFinPrevisionnelle(new \DateTime('2026-12-31'))
            ->setStatut(Project::STATUS_EN_COURS)
            ->setCouleur('#3B82F6')
            ->setNotes('Maison individuelle RT2020, plain-pied avec garage double. Terrain de 800 m². Permis de construire obtenu en août 2025.');
        $proj1->addCollaborator($collabMercier);
        $proj1->addCollaborator($collabDubois);
        $proj1->addCollaborator($collabBernard);
        $manager->persist($proj1);

        // Phases projet 1 (en_cours – avancé)
        $phases1 = [
            ['ESQ', 100, 1, '2025-09-01', '2025-09-30'],
            ['APS', 100, 2, '2025-10-01', '2025-10-31'],
            ['APD', 100, 3, '2025-11-01', '2025-11-30'],
            ['PRO', 80,  4, '2025-12-01', null],
            ['DCE', 40,  5, '2026-01-15', null],
            ['ACT', 0,   6, null, null],
            ['VISA', 0,  7, null, null],
            ['DET', 0,   8, null, null],
            ['AOR', 0,   9, null, null],
        ];
        foreach ($phases1 as $i => [$code, $avanc, $ordre, $deb, $fin]) {
            $phase = new ProjectPhase();
            $phase->setProject($proj1)
                ->setPhase($code)
                ->setAvancement($avanc)
                ->setOrdre($ordre)
                ->setDateDebut($deb ? new \DateTime($deb) : null)
                ->setDateFin($fin ? new \DateTime($fin) : null);
            $manager->persist($phase);
        }

        // Project 2 – Rénovation Médiathèque
        $proj2 = new Project();
        $proj2->setReference('PROJ-2026-002')
            ->setNom('Rénovation Médiathèque')
            ->setClient($clientMairie)
            ->setAdresseChantier('Place de la République, 33000 Bordeaux')
            ->setSurface('850.00')
            ->setMontantHonoraires('120000.00')
            ->setBudgetPrevisionnel('1200000.00')
            ->setDateDebut(new \DateTime('2025-06-01'))
            ->setDateFinPrevisionnelle(new \DateTime('2027-06-30'))
            ->setStatut(Project::STATUS_EN_COURS)
            ->setCouleur('#8B5CF6')
            ->setNotes('Réhabilitation complète de la médiathèque municipale. Mise aux normes accessibilité PMR. Coordination avec services de la ville.');
        $proj2->addCollaborator($collabMercier);
        $proj2->addCollaborator($collabFontaine);
        $proj2->addCollaborator($collabPetit);
        $proj2->addCollaborator($collabDubois);
        $manager->persist($proj2);

        $phases2 = [
            ['ESQ', 100, 1, '2025-06-01', '2025-07-15'],
            ['APS', 100, 2, '2025-07-16', '2025-09-15'],
            ['APD', 100, 3, '2025-09-16', '2025-11-30'],
            ['PRO', 100, 4, '2025-12-01', '2026-01-31'],
            ['DCE', 90,  5, '2026-02-01', null],
            ['ACT', 20,  6, '2026-03-01', null],
            ['VISA', 0,  7, null, null],
            ['DET', 0,   8, null, null],
            ['AOR', 0,   9, null, null],
        ];
        foreach ($phases2 as [$code, $avanc, $ordre, $deb, $fin]) {
            $phase = new ProjectPhase();
            $phase->setProject($proj2)
                ->setPhase($code)
                ->setAvancement($avanc)
                ->setOrdre($ordre)
                ->setDateDebut($deb ? new \DateTime($deb) : null)
                ->setDateFin($fin ? new \DateTime($fin) : null);
            $manager->persist($phase);
        }

        // Project 3 – Extension habitation Dupont
        $proj3 = new Project();
        $proj3->setReference('PROJ-2026-003')
            ->setNom('Extension habitation Dupont')
            ->setClient($clientDupont)
            ->setAdresseChantier('8 impasse des Roses, 33400 Talence')
            ->setSurface('45.00')
            ->setMontantHonoraires('15000.00')
            ->setBudgetPrevisionnel('95000.00')
            ->setDateDebut(new \DateTime('2026-02-01'))
            ->setDateFinPrevisionnelle(new \DateTime('2026-11-30'))
            ->setStatut(Project::STATUS_EN_ATTENTE)
            ->setCouleur('#F59E0B')
            ->setNotes('Extension de 45 m² en rez-de-chaussée. Création d\'une suite parentale. En attente de la décision du client pour démarrer.');
        $proj3->addCollaborator($collabMercier);
        $proj3->addCollaborator($collabBernard);
        $manager->persist($proj3);

        $phases3 = [
            ['ESQ', 60, 1, '2026-02-01', null],
            ['APS', 0,  2, null, null],
            ['APD', 0,  3, null, null],
            ['PRO', 0,  4, null, null],
            ['DCE', 0,  5, null, null],
            ['ACT', 0,  6, null, null],
            ['VISA', 0, 7, null, null],
            ['DET', 0,  8, null, null],
            ['AOR', 0,  9, null, null],
        ];
        foreach ($phases3 as [$code, $avanc, $ordre, $deb, $fin]) {
            $phase = new ProjectPhase();
            $phase->setProject($proj3)
                ->setPhase($code)
                ->setAvancement($avanc)
                ->setOrdre($ordre)
                ->setDateDebut($deb ? new \DateTime($deb) : null)
                ->setDateFin($fin ? new \DateTime($fin) : null);
            $manager->persist($phase);
        }

        // Project 4 – Aménagement bureaux Immo+
        $proj4 = new Project();
        $proj4->setReference('PROJ-2026-004')
            ->setNom('Aménagement bureaux Immo+')
            ->setClient($clientImmo)
            ->setAdresseChantier('75 cours de l\'Intendance, 3ème étage, 33000 Bordeaux')
            ->setSurface('320.00')
            ->setMontantHonoraires('35000.00')
            ->setBudgetPrevisionnel('280000.00')
            ->setDateDebut(new \DateTime('2025-03-01'))
            ->setDateFinPrevisionnelle(new \DateTime('2025-12-15'))
            ->setStatut(Project::STATUS_TERMINE)
            ->setCouleur('#10B981')
            ->setNotes('Aménagement intérieur de 320 m² de bureaux. Livraison effectuée en décembre 2025. Réception sans réserve.');
        $proj4->addCollaborator($collabFontaine);
        $proj4->addCollaborator($collabDubois);
        $manager->persist($proj4);

        $phases4 = [
            ['ESQ', 100, 1, '2025-03-01', '2025-03-31'],
            ['APS', 100, 2, '2025-04-01', '2025-04-30'],
            ['APD', 100, 3, '2025-05-01', '2025-05-31'],
            ['PRO', 100, 4, '2025-06-01', '2025-06-30'],
            ['DCE', 100, 5, '2025-07-01', '2025-07-31'],
            ['ACT', 100, 6, '2025-08-01', '2025-08-31'],
            ['VISA', 100, 7, '2025-09-01', '2025-09-30'],
            ['DET', 100, 8, '2025-10-01', '2025-11-30'],
            ['AOR', 100, 9, '2025-12-01', '2025-12-15'],
        ];
        foreach ($phases4 as [$code, $avanc, $ordre, $deb, $fin]) {
            $phase = new ProjectPhase();
            $phase->setProject($proj4)
                ->setPhase($code)
                ->setAvancement($avanc)
                ->setOrdre($ordre)
                ->setDateDebut($deb ? new \DateTime($deb) : null)
                ->setDateFin($fin ? new \DateTime($fin) : null);
            $manager->persist($phase);
        }

        // Project 5 – Villa contemporaine Martin
        $proj5 = new Project();
        $proj5->setReference('PROJ-2026-005')
            ->setNom('Villa contemporaine Martin')
            ->setClient($clientMartin)
            ->setAdresseChantier('Parcelle AC 142, chemin des Cèdres, 33270 Floirac')
            ->setSurface('220.00')
            ->setMontantHonoraires('55000.00')
            ->setBudgetPrevisionnel('450000.00')
            ->setDateDebut(new \DateTime('2025-11-01'))
            ->setDateFinPrevisionnelle(new \DateTime('2027-03-31'))
            ->setStatut(Project::STATUS_EN_COURS)
            ->setCouleur('#EC4899')
            ->setNotes('Villa contemporaine R+1 avec piscine. Architecture bioclimatique, grandes baies vitrées orientées sud. Permis en cours d\'instruction.');
        $proj5->addCollaborator($collabMercier);
        $proj5->addCollaborator($collabFontaine);
        $proj5->addCollaborator($collabPetit);
        $proj5->addCollaborator($collabBernard);
        $manager->persist($proj5);

        $phases5 = [
            ['ESQ', 100, 1, '2025-11-01', '2025-11-30'],
            ['APS', 100, 2, '2025-12-01', '2026-01-15'],
            ['APD', 70,  3, '2026-01-16', null],
            ['PRO', 0,   4, null, null],
            ['DCE', 0,   5, null, null],
            ['ACT', 0,   6, null, null],
            ['VISA', 0,  7, null, null],
            ['DET', 0,   8, null, null],
            ['AOR', 0,   9, null, null],
        ];
        foreach ($phases5 as [$code, $avanc, $ordre, $deb, $fin]) {
            $phase = new ProjectPhase();
            $phase->setProject($proj5)
                ->setPhase($code)
                ->setAvancement($avanc)
                ->setOrdre($ordre)
                ->setDateDebut($deb ? new \DateTime($deb) : null)
                ->setDateFin($fin ? new \DateTime($fin) : null);
            $manager->persist($phase);
        }

        // Project 6 – Réhabilitation entrepôt
        $proj6 = new Project();
        $proj6->setReference('PROJ-2026-006')
            ->setNom('Réhabilitation entrepôt')
            ->setClient($clientImmo)
            ->setAdresseChantier('Zone industrielle des Jalles, 12 rue des Forges, 33290 Blanquefort')
            ->setSurface('1200.00')
            ->setMontantHonoraires('85000.00')
            ->setBudgetPrevisionnel('750000.00')
            ->setDateDebut(new \DateTime('2025-07-01'))
            ->setDateFinPrevisionnelle(new \DateTime('2027-09-30'))
            ->setStatut(Project::STATUS_SUSPENDU)
            ->setCouleur('#6B7280')
            ->setNotes('Réhabilitation d\'un entrepôt industriel en lofts résidentiels. Suspendu depuis janvier 2026 en attente de financement bancaire du client.');
        $proj6->addCollaborator($collabMercier);
        $proj6->addCollaborator($collabPetit);
        $proj6->addCollaborator($collabDubois);
        $manager->persist($proj6);

        $phases6 = [
            ['ESQ', 100, 1, '2025-07-01', '2025-07-31'],
            ['APS', 100, 2, '2025-08-01', '2025-09-30'],
            ['APD', 50,  3, '2025-10-01', null],
            ['PRO', 0,   4, null, null],
            ['DCE', 0,   5, null, null],
            ['ACT', 0,   6, null, null],
            ['VISA', 0,  7, null, null],
            ['DET', 0,   8, null, null],
            ['AOR', 0,   9, null, null],
        ];
        foreach ($phases6 as [$code, $avanc, $ordre, $deb, $fin]) {
            $phase = new ProjectPhase();
            $phase->setProject($proj6)
                ->setPhase($code)
                ->setAvancement($avanc)
                ->setOrdre($ordre)
                ->setDateDebut($deb ? new \DateTime($deb) : null)
                ->setDateFin($fin ? new \DateTime($fin) : null);
            $manager->persist($phase);
        }

        // =====================================================================
        // QUOTES
        // =====================================================================

        // DEV-2026-001 – Maison individuelle Les Jardins (accepté)
        $quote1 = new Quote();
        $quote1->setNumero('DEV-2026-001')
            ->setClient($clientSCI)
            ->setProject($proj1)
            ->setObjet('Mission complète - Maison individuelle Les Jardins')
            ->setDateCreation(new \DateTime('2025-08-15'))
            ->setDateValidite(new \DateTime('2025-09-15'))
            ->setStatut(Quote::STATUS_ACCEPTE)
            ->setTauxTVA('20.00');

        $qLine1 = new QuoteLine();
        $qLine1->setQuote($quote1)->setDesignation('Phase ESQ + APS + APD - Études de conception')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('15000.00')->setOrdre(1);
        $qLine1->calculateMontant();
        $manager->persist($qLine1);

        $qLine2 = new QuoteLine();
        $qLine2->setQuote($quote1)->setDesignation('Phase PRO + DCE - Dossier de consultation des entreprises')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('12000.00')->setOrdre(2);
        $qLine2->calculateMontant();
        $manager->persist($qLine2);

        $qLine3 = new QuoteLine();
        $qLine3->setQuote($quote1)->setDesignation('Phase ACT + VISA - Assistance contrats et visa des plans d\'exécution')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('8000.00')->setOrdre(3);
        $qLine3->calculateMontant();
        $manager->persist($qLine3);

        $qLine4 = new QuoteLine();
        $qLine4->setQuote($quote1)->setDesignation('Phase DET + AOR - Direction de chantier et réception')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('10000.00')->setOrdre(4);
        $qLine4->calculateMontant();
        $manager->persist($qLine4);

        $quote1->addLine($qLine1)->addLine($qLine2)->addLine($qLine3)->addLine($qLine4);
        $quote1->calculateTotals();
        $manager->persist($quote1);

        // DEV-2026-002 – Rénovation Médiathèque (accepté)
        $quote2 = new Quote();
        $quote2->setNumero('DEV-2026-002')
            ->setClient($clientMairie)
            ->setProject($proj2)
            ->setObjet('Mission de maîtrise d\'œuvre - Rénovation Médiathèque')
            ->setDateCreation(new \DateTime('2025-05-10'))
            ->setDateValidite(new \DateTime('2025-06-10'))
            ->setStatut(Quote::STATUS_ACCEPTE)
            ->setTauxTVA('20.00');

        $q2l1 = new QuoteLine();
        $q2l1->setQuote($quote2)->setDesignation('Études de diagnostic et programmation')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('18000.00')->setOrdre(1);
        $q2l1->calculateMontant();
        $manager->persist($q2l1);

        $q2l2 = new QuoteLine();
        $q2l2->setQuote($quote2)->setDesignation('Études de conception ESQ à APD')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('45000.00')->setOrdre(2);
        $q2l2->calculateMontant();
        $manager->persist($q2l2);

        $q2l3 = new QuoteLine();
        $q2l3->setQuote($quote2)->setDesignation('Études PRO, DCE et assistance aux contrats')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('32000.00')->setOrdre(3);
        $q2l3->calculateMontant();
        $manager->persist($q2l3);

        $q2l4 = new QuoteLine();
        $q2l4->setQuote($quote2)->setDesignation('Direction de l\'exécution des travaux et OPC')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('25000.00')->setOrdre(4);
        $q2l4->calculateMontant();
        $manager->persist($q2l4);

        $quote2->addLine($q2l1)->addLine($q2l2)->addLine($q2l3)->addLine($q2l4);
        $quote2->calculateTotals();
        $manager->persist($quote2);

        // DEV-2026-003 – Extension habitation Dupont (envoyé)
        $quote3 = new Quote();
        $quote3->setNumero('DEV-2026-003')
            ->setClient($clientDupont)
            ->setProject($proj3)
            ->setObjet('Mission partielle - Extension habitation Dupont')
            ->setDateCreation(new \DateTime('2026-01-20'))
            ->setDateValidite(new \DateTime('2026-02-20'))
            ->setStatut(Quote::STATUS_ENVOYE)
            ->setTauxTVA('20.00');

        $q3l1 = new QuoteLine();
        $q3l1->setQuote($quote3)->setDesignation('Études ESQ et APS - Avant-projet')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('4500.00')->setOrdre(1);
        $q3l1->calculateMontant();
        $manager->persist($q3l1);

        $q3l2 = new QuoteLine();
        $q3l2->setQuote($quote3)->setDesignation('Dossier permis de construire (APD + dossier PC)')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('3500.00')->setOrdre(2);
        $q3l2->calculateMontant();
        $manager->persist($q3l2);

        $q3l3 = new QuoteLine();
        $q3l3->setQuote($quote3)->setDesignation('PRO + DCE + consultation entreprises')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('4000.00')->setOrdre(3);
        $q3l3->calculateMontant();
        $manager->persist($q3l3);

        $q3l4 = new QuoteLine();
        $q3l4->setQuote($quote3)->setDesignation('Suivi de chantier (DET) - forfait 6 mois')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('3000.00')->setOrdre(4);
        $q3l4->calculateMontant();
        $manager->persist($q3l4);

        $quote3->addLine($q3l1)->addLine($q3l2)->addLine($q3l3)->addLine($q3l4);
        $quote3->calculateTotals();
        $manager->persist($quote3);

        // DEV-2026-004 – Villa contemporaine Martin (accepté)
        $quote4 = new Quote();
        $quote4->setNumero('DEV-2026-004')
            ->setClient($clientMartin)
            ->setProject($proj5)
            ->setObjet('Mission complète - Villa contemporaine Martin')
            ->setDateCreation(new \DateTime('2025-10-05'))
            ->setDateValidite(new \DateTime('2025-11-05'))
            ->setStatut(Quote::STATUS_ACCEPTE)
            ->setTauxTVA('20.00');

        $q4l1 = new QuoteLine();
        $q4l1->setQuote($quote4)->setDesignation('Études de conception ESQ, APS et APD')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('18000.00')->setOrdre(1);
        $q4l1->calculateMontant();
        $manager->persist($q4l1);

        $q4l2 = new QuoteLine();
        $q4l2->setQuote($quote4)->setDesignation('Dossier permis de construire et études PRO')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('15000.00')->setOrdre(2);
        $q4l2->calculateMontant();
        $manager->persist($q4l2);

        $q4l3 = new QuoteLine();
        $q4l3->setQuote($quote4)->setDesignation('DCE, ACT et VISA plans d\'exécution')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('12000.00')->setOrdre(3);
        $q4l3->calculateMontant();
        $manager->persist($q4l3);

        $q4l4 = new QuoteLine();
        $q4l4->setQuote($quote4)->setDesignation('Direction de chantier DET + AOR')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('10000.00')->setOrdre(4);
        $q4l4->calculateMontant();
        $manager->persist($q4l4);

        $quote4->addLine($q4l1)->addLine($q4l2)->addLine($q4l3)->addLine($q4l4);
        $quote4->calculateTotals();
        $manager->persist($quote4);

        // DEV-2026-005 – Réhabilitation entrepôt (brouillon)
        $quote5 = new Quote();
        $quote5->setNumero('DEV-2026-005')
            ->setClient($clientImmo)
            ->setProject($proj6)
            ->setObjet('Avenant mission - Réhabilitation entrepôt en lofts')
            ->setDateCreation(new \DateTime('2026-01-10'))
            ->setDateValidite(new \DateTime('2026-02-10'))
            ->setStatut(Quote::STATUS_BROUILLON)
            ->setTauxTVA('20.00');

        $q5l1 = new QuoteLine();
        $q5l1->setQuote($quote5)->setDesignation('Études complémentaires structure béton existant')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('12000.00')->setOrdre(1);
        $q5l1->calculateMontant();
        $manager->persist($q5l1);

        $q5l2 = new QuoteLine();
        $q5l2->setQuote($quote5)->setDesignation('Coordination SPS (Sécurité Protection Santé)')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('8500.00')->setOrdre(2);
        $q5l2->calculateMontant();
        $manager->persist($q5l2);

        $q5l3 = new QuoteLine();
        $q5l3->setQuote($quote5)->setDesignation('Mission OPC - Ordonnancement Pilotage Coordination')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('15000.00')->setOrdre(3);
        $q5l3->calculateMontant();
        $manager->persist($q5l3);

        $quote5->addLine($q5l1)->addLine($q5l2)->addLine($q5l3);
        $quote5->calculateTotals();
        $manager->persist($quote5);

        // =====================================================================
        // INVOICES
        // =====================================================================

        // FAC-2026-001 – SCI Les Jardins (payée)
        $inv1 = new Invoice();
        $inv1->setNumero('FAC-2026-001')
            ->setClient($clientSCI)
            ->setProject($proj1)
            ->setQuote($quote1)
            ->setObjet('Acompte 30% - Maison individuelle Les Jardins - Phase ESQ/APS')
            ->setDateEmission(new \DateTime('2025-10-01'))
            ->setDateEcheance(new \DateTime('2025-10-31'))
            ->setStatut(Invoice::STATUS_PAYEE)
            ->setDatePaiement(new \DateTime('2025-10-22'))
            ->setTauxTVA('20.00');

        $i1l1 = new InvoiceLine();
        $i1l1->setInvoice($inv1)->setDesignation('Acompte 30% sur honoraires - Phases ESQ et APS')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('13500.00')->setOrdre(1);
        $i1l1->calculateMontant();
        $manager->persist($i1l1);

        $inv1->addLine($i1l1);
        $inv1->calculateTotals();
        $manager->persist($inv1);

        // FAC-2026-002 – Mairie de Bordeaux (payée)
        $inv2 = new Invoice();
        $inv2->setNumero('FAC-2026-002')
            ->setClient($clientMairie)
            ->setProject($proj2)
            ->setQuote($quote2)
            ->setObjet('Situation n°1 - Études ESQ/APS/APD - Rénovation Médiathèque')
            ->setDateEmission(new \DateTime('2025-12-01'))
            ->setDateEcheance(new \DateTime('2026-01-01'))
            ->setStatut(Invoice::STATUS_PAYEE)
            ->setDatePaiement(new \DateTime('2025-12-28'))
            ->setTauxTVA('20.00');

        $i2l1 = new InvoiceLine();
        $i2l1->setInvoice($inv2)->setDesignation('Études de diagnostic et programmation - solde')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('18000.00')->setOrdre(1);
        $i2l1->calculateMontant();
        $manager->persist($i2l1);

        $i2l2 = new InvoiceLine();
        $i2l2->setInvoice($inv2)->setDesignation('Études ESQ, APS et APD - 80%')->setQuantite('0.80')->setUnite('forfait')->setPrixUnitaireHT('45000.00')->setOrdre(2);
        $i2l2->calculateMontant();
        $manager->persist($i2l2);

        $inv2->addLine($i2l1)->addLine($i2l2);
        $inv2->calculateTotals();
        $manager->persist($inv2);

        // FAC-2026-003 – SARL Immo+ (payée – projet terminé)
        $inv3 = new Invoice();
        $inv3->setNumero('FAC-2026-003')
            ->setClient($clientImmo)
            ->setProject($proj4)
            ->setObjet('Solde honoraires - Aménagement bureaux Immo+')
            ->setDateEmission(new \DateTime('2026-01-05'))
            ->setDateEcheance(new \DateTime('2026-02-05'))
            ->setStatut(Invoice::STATUS_PAYEE)
            ->setDatePaiement(new \DateTime('2026-01-30'))
            ->setTauxTVA('20.00');

        $i3l1 = new InvoiceLine();
        $i3l1->setInvoice($inv3)->setDesignation('Solde mission MOE - Aménagement bureaux 320 m²')->setQuantite('1.00')->setUnite('forfait')->setPrixUnitaireHT('35000.00')->setOrdre(1);
        $i3l1->calculateMontant();
        $manager->persist($i3l1);

        $inv3->addLine($i3l1);
        $inv3->calculateTotals();
        $manager->persist($inv3);

        // FAC-2026-004 – Mairie de Bordeaux (en_attente)
        $inv4 = new Invoice();
        $inv4->setNumero('FAC-2026-004')
            ->setClient($clientMairie)
            ->setProject($proj2)
            ->setQuote($quote2)
            ->setObjet('Situation n°2 - Phases PRO/DCE - Rénovation Médiathèque')
            ->setDateEmission(new \DateTime('2026-02-15'))
            ->setDateEcheance(new \DateTime('2026-03-17'))
            ->setStatut(Invoice::STATUS_EN_ATTENTE)
            ->setTauxTVA('20.00');

        $i4l1 = new InvoiceLine();
        $i4l1->setInvoice($inv4)->setDesignation('Études PRO, DCE et assistance contrats - 70%')->setQuantite('0.70')->setUnite('forfait')->setPrixUnitaireHT('32000.00')->setOrdre(1);
        $i4l1->calculateMontant();
        $manager->persist($i4l1);

        $i4l2 = new InvoiceLine();
        $i4l2->setInvoice($inv4)->setDesignation('Solde études ESQ/APS/APD (20% restants)')->setQuantite('0.20')->setUnite('forfait')->setPrixUnitaireHT('45000.00')->setOrdre(2);
        $i4l2->calculateMontant();
        $manager->persist($i4l2);

        $inv4->addLine($i4l1)->addLine($i4l2);
        $inv4->calculateTotals();
        $manager->persist($inv4);

        // =====================================================================
        // TIME ENTRIES (50+)
        // =====================================================================

        $timeEntries = [
            // Janvier 2026 – Projet 1
            [$collabMercier, $proj1, 'PRO', '2026-01-05', '4.00', 'Réunion de validation APD avec le client', true],
            [$collabMercier, $proj1, 'PRO', '2026-01-07', '6.00', 'Élaboration des plans d\'exécution RDC', true],
            [$collabDubois,  $proj1, 'PRO', '2026-01-08', '7.50', 'Dessin plans RDC et coupe longitudinale', true],
            [$collabDubois,  $proj1, 'PRO', '2026-01-09', '7.50', 'Dessin plans étage et façades', true],
            [$collabBernard, $proj1, 'PRO', '2026-01-10', '4.00', 'Mise en page et archivage plans A3', true],
            [$collabMercier, $proj1, 'DCE', '2026-01-14', '5.00', 'Rédaction CCTP lot gros œuvre', true],
            [$collabMercier, $proj1, 'DCE', '2026-01-16', '4.50', 'Rédaction CCTP lot charpente et couverture', true],
            [$collabDubois,  $proj1, 'DCE', '2026-01-19', '6.00', 'Plans DCE façades et coupes cotées', true],
            [$collabBernard, $proj1, 'DCE', '2026-01-21', '3.50', 'Montage dossier consultation PDF', true],

            // Janvier 2026 – Projet 2
            [$collabMercier, $proj2, 'DCE', '2026-01-06', '7.00', 'Coordination réunion DCE médiathèque', true],
            [$collabFontaine, $proj2, 'DCE', '2026-01-06', '6.00', 'Aménagement intérieur - plans mobilier', true],
            [$collabPetit,   $proj2, 'DCE', '2026-01-07', '8.00', 'Note de calcul structure renforcement planchers', true],
            [$collabDubois,  $proj2, 'DCE', '2026-01-08', '7.00', 'Plans structure béton existant', true],
            [$collabMercier, $proj2, 'ACT', '2026-01-27', '4.00', 'Analyse offres lot maçonnerie', true],
            [$collabFontaine, $proj2, 'ACT', '2026-01-28', '3.50', 'Analyse offres lots intérieurs', true],

            // Janvier 2026 – Projet 5
            [$collabMercier, $proj5, 'APD', '2026-01-12', '6.00', 'Développement APD villa Martin - plans RDC', true],
            [$collabFontaine, $proj5, 'APD', '2026-01-13', '5.50', 'Concept aménagement intérieur et ambiances', true],
            [$collabPetit,   $proj5, 'APD', '2026-01-15', '6.00', 'Prédimensionnement structure béton/bois', true],
            [$collabBernard, $proj5, 'APD', '2026-01-20', '4.00', 'Recherche fournisseurs matériaux bioclimatiques', false],

            // Janvier 2026 – Projet 3
            [$collabMercier, $proj3, 'ESQ', '2026-01-22', '3.00', 'Première esquisse extension Dupont', true],
            [$collabBernard, $proj3, 'ESQ', '2026-01-22', '2.00', 'Analyse réglementation PLU Talence', false],

            // Février 2026 – Projet 1
            [$collabMercier, $proj1, 'DCE', '2026-02-02', '5.00', 'Finalisation dossier DCE complet', true],
            [$collabDubois,  $proj1, 'DCE', '2026-02-03', '7.50', 'Plans d\'exécution menuiseries extérieures', true],
            [$collabDubois,  $proj1, 'DCE', '2026-02-04', '6.00', 'Plans d\'exécution escalier et garde-corps', true],
            [$collabBernard, $proj1, 'DCE', '2026-02-05', '5.00', 'Envoi dossier aux entreprises consultées', true],
            [$collabMercier, $proj1, 'DCE', '2026-02-17', '4.00', 'Analyse offres lot fondations et gros œuvre', true],
            [$collabMercier, $proj1, 'DCE', '2026-02-24', '4.50', 'Analyse offres lots second œuvre', true],

            // Février 2026 – Projet 2
            [$collabMercier, $proj2, 'ACT', '2026-02-03', '5.00', 'Mise au point marché lot gros œuvre', true],
            [$collabFontaine, $proj2, 'ACT', '2026-02-04', '4.50', 'Mise au point marché lots intérieurs', true],
            [$collabPetit,   $proj2, 'ACT', '2026-02-05', '3.00', 'Vérification plans structure entreprises', true],
            [$collabDubois,  $proj2, 'ACT', '2026-02-10', '6.00', 'Plans de synthèse coordination lot gros œuvre', true],
            [$collabMercier, $proj2, 'ACT', '2026-02-18', '3.00', 'Réunion de mise au point pré-chantier', true],

            // Février 2026 – Projet 5
            [$collabMercier, $proj5, 'APD', '2026-02-06', '5.00', 'Réunion APD avec Mme Martin - présentation maquette', true],
            [$collabFontaine, $proj5, 'APD', '2026-02-06', '4.00', 'Présentation planches ambiances intérieures', true],
            [$collabPetit,   $proj5, 'APD', '2026-02-09', '6.00', 'Note de calcul thermique RT2020', true],
            [$collabDubois,  $proj5, 'APD', '2026-02-11', '7.00', 'Plans APD définitifs tous niveaux', true],
            [$collabBernard, $proj5, 'APD', '2026-02-12', '4.00', 'Montage dossier permis de construire', true],
            [$collabBernard, $proj5, 'APD', '2026-02-19', '5.00', 'Constitution dossier PC - pièces administratives', true],

            // Mars 2026 – Projet 1
            [$collabMercier, $proj1, 'DCE', '2026-03-03', '4.00', 'Réunion analyse offres avec le client', true],
            [$collabBernard, $proj1, 'DCE', '2026-03-04', '3.50', 'Mise à jour tableaux comparatifs offres', true],
            [$collabMercier, $proj1, 'DCE', '2026-03-10', '3.00', 'Rapport de présentation des offres', true],

            // Mars 2026 – Projet 2
            [$collabMercier, $proj2, 'ACT', '2026-03-04', '4.00', 'Première réunion de chantier médiathèque', true],
            [$collabFontaine, $proj2, 'ACT', '2026-03-04', '3.50', 'Présence réunion chantier - lots intérieurs', true],
            [$collabPetit,   $proj2, 'ACT', '2026-03-05', '4.00', 'Vérification implantation fondations', true],
            [$collabDubois,  $proj2, 'ACT', '2026-03-11', '5.00', 'Mise à jour plans suite constats terrain', true],
            [$collabMercier, $proj2, 'ACT', '2026-03-18', '3.00', 'Deuxième réunion de chantier', true],

            // Mars 2026 – Projet 5
            [$collabMercier, $proj5, 'APD', '2026-03-05', '3.00', 'Finalisation notice descriptive PC', true],
            [$collabFontaine, $proj5, 'APD', '2026-03-06', '4.00', 'Plans de coupe et façades PC', true],
            [$collabDubois,  $proj5, 'APD', '2026-03-09', '6.00', 'Insertion paysagère et plan masse PC', true],
            [$collabBernard, $proj5, 'APD', '2026-03-10', '3.00', 'Dépôt dossier PC en mairie de Floirac', false],

            // Mars 2026 – Projet 3
            [$collabMercier, $proj3, 'ESQ', '2026-03-12', '2.50', 'Présentation esquisse à M. Dupont', true],
            [$collabBernard, $proj3, 'ESQ', '2026-03-13', '2.00', 'Modifications suite retour client', true],
        ];

        foreach ($timeEntries as [$collab, $project, $phase, $date, $heures, $desc, $facturable]) {
            $te = new TimeEntry();
            $te->setCollaborator($collab)
                ->setProject($project)
                ->setPhase($phase)
                ->setDate(new \DateTime($date))
                ->setHeures($heures)
                ->setDescription($desc)
                ->setFacturable($facturable);
            $manager->persist($te);
        }

        // =====================================================================
        // EXPENSES
        // =====================================================================

        $expenses = [
            // Projet 1
            [$proj1, '2026-01-08', 'deplacements',  '85.00',   'Déplacement chantier Mérignac - 2 allers-retours',  'Note de frais Thomas Mercier'],
            [$proj1, '2026-01-15', 'impressions',   '42.50',   'Impression plans A1 x10 et A3 x20',                 'Repro Sud Bordeaux'],
            [$proj1, '2026-02-10', 'deplacements',  '65.00',   'Déplacement réunion DCE avec entreprises',           'Note de frais Marc Dubois'],
            [$proj1, '2026-02-20', 'impressions',   '156.00',  'Impression dossier DCE complet x5 exemplaires',      'Repro Sud Bordeaux'],
            [$proj1, '2026-03-05', 'divers',        '35.00',   'Frais de coursier envoi dossier',                    'Chronopost'],

            // Projet 2
            [$proj2, '2026-01-10', 'deplacements',  '12.50',   'Déplacement réunion mairie',                         'Note de frais Thomas Mercier'],
            [$proj2, '2026-01-20', 'impressions',   '320.00',  'Impression dossier DCE médiathèque - 6 lots',        'Reprogravure Bordeaux'],
            [$proj2, '2026-01-25', 'sous_traitance','2800.00', 'BET thermique - étude thermodynamique',              'Cabinet Thermique Consultants'],
            [$proj2, '2026-02-08', 'sous_traitance','1500.00', 'Géomètre - relevé topographique existant',           'SARL Géomètre Associés'],
            [$proj2, '2026-03-04', 'deplacements',  '12.50',   'Déplacement réunion chantier n°1',                   'Note de frais Thomas Mercier'],
            [$proj2, '2026-03-18', 'impressions',   '85.00',   'Plans de chantier mis à jour x4 exemplaires',        'Reprogravure Bordeaux'],

            // Projet 4
            [$proj4, '2025-11-15', 'logiciels',     '89.00',   'Licence Lumion rendu 3D mensuel',                    'Lumion BV'],
            [$proj4, '2025-12-01', 'deplacements',  '45.00',   'Déplacements chantier décembre',                     'Note de frais Julie Fontaine'],

            // Projet 5
            [$proj5, '2026-01-25', 'logiciels',     '149.00',  'Licence ArchiCAD - renouvellement annuel',           'Graphisoft France'],
            [$proj5, '2026-02-14', 'impressions',   '68.00',   'Impression maquette APD A0 x3',                      'Repro Sud Bordeaux'],
            [$proj5, '2026-03-10', 'deplacements',  '24.00',   'Déplacement dépôt PC mairie de Floirac',             'Note de frais Lucas Bernard'],
            [$proj5, '2026-03-20', 'fournitures',   '37.50',   'Maquette carton projet villa',                       'Leroy Merlin'],

            // Projet 3
            [$proj3, '2026-01-22', 'deplacements',  '28.00',   'Déplacement visite terrain Talence',                 'Note de frais Thomas Mercier'],
            [$proj3, '2026-02-05', 'fournitures',   '15.00',   'Fournitures bureau pour dossier',                    'Bureau Vallée'],

            // Projet 6
            [$proj6, '2025-10-15', 'sous_traitance','3500.00', 'Diagnostic amiante et plomb - entrepôt',             'Diagex Environnement'],
            [$proj6, '2025-11-20', 'deplacements',  '55.00',   'Déplacements chantier Blanquefort',                  'Note de frais Marc Dubois'],
        ];

        foreach ($expenses as [$project, $date, $categorie, $montant, $description, $fournisseur]) {
            $expense = new Expense();
            $expense->setProject($project)
                ->setDate(new \DateTime($date))
                ->setCategorie($categorie)
                ->setMontant($montant)
                ->setDescription($description)
                ->setFournisseur($fournisseur);
            $manager->persist($expense);
        }

        // =====================================================================
        // EVENTS
        // =====================================================================

        $events = [
            // Réunions chantier
            ['Réunion de chantier n°1 - Médiathèque',      'reunion_chantier', '2026-03-04 09:00', '2026-03-04 11:00', $proj2, 'Place de la République, 33000 Bordeaux',           '#8B5CF6', 'Première réunion de chantier. Présence maître d\'ouvrage, MOE, entreprise gros œuvre.'],
            ['Réunion de chantier n°2 - Médiathèque',      'reunion_chantier', '2026-03-18 09:00', '2026-03-18 11:00', $proj2, 'Place de la République, 33000 Bordeaux',           '#8B5CF6', 'Point sur avancement fondations et dallage RDC.'],
            ['Réunion de chantier n°3 - Médiathèque',      'reunion_chantier', '2026-04-01 09:00', '2026-04-01 11:00', $proj2, 'Place de la République, 33000 Bordeaux',           '#8B5CF6', 'Point sur maçonnerie et pose charpente.'],

            // RDV clients
            ['RDV validation APD - Mme Martin',             'rdv_client',      '2026-02-06 14:00', '2026-02-06 16:00', $proj5, '15 rue des Arts, 33000 Bordeaux',                  '#EC4899', 'Présentation APD et maquette 3D. Validation avec Mme Martin.'],
            ['RDV dépôt dossier PC - Villa Martin',         'rdv_client',      '2026-03-10 10:00', '2026-03-10 11:00', $proj5, 'Mairie de Floirac, 33270 Floirac',                 '#EC4899', 'Dépôt du dossier de permis de construire en mairie.'],
            ['RDV présentation esquisse - M. Dupont',       'rdv_client',      '2026-03-12 17:00', '2026-03-12 18:30', $proj3, '8 impasse des Roses, 33400 Talence',               '#F59E0B', 'Présentation des esquisses d\'extension au client.'],
            ['RDV analyse offres DCE - SCI Les Jardins',    'rdv_client',      '2026-03-03 10:00', '2026-03-03 12:00', $proj1, '15 rue des Arts, 33000 Bordeaux',                  '#3B82F6', 'Réunion d\'analyse des offres avec M. Laurent Moreau.'],
            ['RDV mise au point marché - Mairie Bordeaux',  'rdv_client',      '2026-02-03 09:00', '2026-02-03 11:00', $proj2, 'Mairie de Bordeaux, Place Pey-Berland',            '#8B5CF6', 'Mise au point des marchés de travaux avec les services de la ville.'],

            // Échéances
            ['Échéance DCE - Maison Les Jardins',           'echeance',        '2026-02-05 00:00', null,                $proj1, null,                                               '#3B82F6', 'Date limite envoi dossier aux entreprises consultées.'],
            ['Échéance offres entreprises - Médiathèque',   'echeance',        '2026-02-28 00:00', null,                $proj2, null,                                               '#8B5CF6', 'Date limite remise offres entreprises DCE médiathèque.'],
            ['Échéance dépôt PC - Villa Martin',            'echeance',        '2026-03-10 00:00', null,                $proj5, null,                                               '#EC4899', 'Date de dépôt du permis de construire en mairie de Floirac.'],
            ['Échéance paiement FAC-2026-004',               'echeance',        '2026-03-17 00:00', null,                $proj2, null,                                               '#EF4444', 'Date d\'échéance facture FAC-2026-004 - Mairie de Bordeaux.'],

            // Livraisons
            ['Livraison plans PRO - Maison Les Jardins',    'livraison',       '2026-01-31 00:00', null,                $proj1, null,                                               '#10B981', 'Livraison dossier PRO complet au client SCI Les Jardins.'],
            ['Livraison dossier DCE - Médiathèque',         'livraison',       '2026-01-31 00:00', null,                $proj2, null,                                               '#10B981', 'Envoi dossier DCE aux entreprises - 6 lots.'],
            ['Livraison APD - Villa contemporaine Martin',  'livraison',       '2026-02-28 00:00', null,                $proj5, null,                                               '#EC4899', 'Livraison dossier APD validé avec plans et notice.'],
            ['Livraison plans DCE - Maison Les Jardins',    'livraison',       '2026-02-28 00:00', null,                $proj1, null,                                               '#3B82F6', 'Livraison dossier DCE complet - 4 lots.'],
        ];

        foreach ($events as [$titre, $type, $debut, $fin, $project, $lieu, $couleur, $desc]) {
            $event = new Event();
            $event->setTitre($titre)
                ->setType($type)
                ->setDateDebut(new \DateTime($debut))
                ->setDateFin($fin ? new \DateTime($fin) : null)
                ->setAllDay($fin === null)
                ->setProject($project)
                ->setLieu($lieu)
                ->setCouleur($couleur)
                ->setDescription($desc);
            $manager->persist($event);
        }

        // =====================================================================
        // DOCUMENTS (metadata only)
        // =====================================================================

        $documents = [
            // Projet 1
            [$proj1, 'Plan RDC - Maison Les Jardins',          'PROJ-2026-001_RDC_PRO_v1.pdf',     'plans',         'v1', 'Plan de rez-de-chaussée phase PRO'],
            [$proj1, 'Plan façades - Maison Les Jardins',      'PROJ-2026-001_FACADES_PRO_v1.pdf', 'plans',         'v1', 'Façades Nord, Sud, Est, Ouest'],
            [$proj1, 'CCTP Gros œuvre',                        'PROJ-2026-001_CCTP_GO_v1.pdf',     'cctp',          'v1', 'Cahier des clauses techniques particulières lot gros œuvre'],
            [$proj1, 'CCTP Charpente couverture',              'PROJ-2026-001_CCTP_CC_v1.pdf',     'cctp',          'v1', 'CCTP lot charpente et couverture'],
            [$proj1, 'Permis de construire - Arrêté',          'PROJ-2026-001_PC_arrete.pdf',      'administratif', 'v1', 'Arrêté de permis de construire obtenu 15/08/2025'],

            // Projet 2
            [$proj2, 'Plans de masse médiathèque',             'PROJ-2026-002_MASSE_DCE_v2.pdf',   'plans',         'v2', 'Plan de masse et situation - dossier DCE'],
            [$proj2, 'Plans RDC médiathèque',                  'PROJ-2026-002_RDC_DCE_v2.pdf',     'plans',         'v2', 'Plan RDC coté et annoté'],
            [$proj2, 'PV Réunion chantier n°1',                'PROJ-2026-002_PV_RC01.pdf',        'pv_chantier',   'v1', 'PV réunion de chantier n°1 du 04/03/2026'],
            [$proj2, 'PV Réunion chantier n°2',                'PROJ-2026-002_PV_RC02.pdf',        'pv_chantier',   'v1', 'PV réunion de chantier n°2 du 18/03/2026'],
            [$proj2, 'CCTP Gros œuvre médiathèque',           'PROJ-2026-002_CCTP_GO_v1.pdf',     'cctp',          'v1', 'CCTP lot 01 - Gros œuvre et démolition'],
            [$proj2, 'Notice accessibilité PMR',               'PROJ-2026-002_NOTICE_PMR.pdf',     'administratif', 'v1', 'Notice d\'accessibilité personnes à mobilité réduite'],

            // Projet 4
            [$proj4, 'Plans aménagement bureaux - livrés',     'PROJ-2026-004_PLANS_AOR_v3.pdf',   'plans',         'v3', 'Plans livrés AOR - état final'],
            [$proj4, 'PV de réception sans réserve',           'PROJ-2026-004_PV_RECEPTION.pdf',   'pv_chantier',   'v1', 'Procès-verbal de réception des travaux - 15/12/2025'],

            // Projet 5
            [$proj5, 'Plans APD villa Martin - RDC + R+1',     'PROJ-2026-005_APD_PLANS_v1.pdf',  'plans',         'v1', 'Plans APD tous niveaux - version soumise pour validation'],
            [$proj5, 'Notice descriptive APD',                 'PROJ-2026-005_APD_NOTICE_v1.pdf', 'administratif', 'v1', 'Notice descriptive du projet - phase APD'],
            [$proj5, 'Insertion paysagère - plan masse PC',    'PROJ-2026-005_PC_MASSE_v1.pdf',   'plans',         'v1', 'Plan masse pour dossier permis de construire'],

            // Projet 6
            [$proj6, 'Rapport diagnostic amiante',             'PROJ-2026-006_DIAG_AMIANTE.pdf',  'administratif', 'v1', 'Diagnostic amiante avant travaux - Diagex Environnement'],
            [$proj6, 'Plans relevé entrepôt existant',         'PROJ-2026-006_RELEVE_EXIST.pdf',  'plans',         'v1', 'Relevé plans existant entrepôt - géomètre'],
        ];

        foreach ($documents as [$project, $nom, $nomFichier, $categorie, $version, $notes]) {
            $doc = new Document();
            $doc->setProject($project)
                ->setNom($nom)
                ->setNomFichier($nomFichier)
                ->setCategorie($categorie)
                ->setVersion($version)
                ->setNotes($notes);
            $manager->persist($doc);
        }

        $manager->flush();
    }
}
