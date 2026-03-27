<?php

namespace App\Controller;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/settings')]
class SettingController extends AbstractController
{
    /**
     * Default setting keys with labels and descriptions.
     */
    private const DEFAULTS = [
        'company_name'        => ['label' => 'Nom de la société',         'default' => 'Mon Cabinet',       'description' => 'Raison sociale'],
        'company_address'     => ['label' => 'Adresse',                   'default' => '',                  'description' => 'Adresse postale'],
        'company_city'        => ['label' => 'Ville',                     'default' => '',                  'description' => 'Ville'],
        'company_zip'         => ['label' => 'Code postal',               'default' => '',                  'description' => 'Code postal'],
        'company_phone'       => ['label' => 'Téléphone',                 'default' => '',                  'description' => 'Numéro de téléphone'],
        'company_email'       => ['label' => 'Email',                     'default' => '',                  'description' => 'Email de contact'],
        'company_siret'       => ['label' => 'SIRET',                     'default' => '',                  'description' => 'Numéro SIRET'],
        'company_tva_number'  => ['label' => 'N° TVA intracommunautaire', 'default' => '',                  'description' => 'TVA intra'],
        'default_tva_rate'    => ['label' => 'Taux TVA par défaut (%)',   'default' => '20.00',             'description' => 'Taux TVA appliqué'],
        'invoice_prefix'      => ['label' => 'Préfixe facture',           'default' => 'FAC',               'description' => 'Préfixe des numéros de facture'],
        'quote_prefix'        => ['label' => 'Préfixe devis',             'default' => 'DEV',               'description' => 'Préfixe des numéros de devis'],
        'invoice_footer'      => ['label' => 'Pied de page facture',      'default' => '',                  'description' => 'Texte affiché en bas de chaque facture'],
        'quote_footer'        => ['label' => 'Pied de page devis',        'default' => '',                  'description' => 'Texte affiché en bas de chaque devis'],
        'currency'            => ['label' => 'Devise',                    'default' => 'EUR',               'description' => 'Code ISO de la devise'],
        'date_format'         => ['label' => 'Format de date',            'default' => 'd/m/Y',             'description' => 'Format PHP de date'],
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private SettingRepository $settingRepository,
    ) {}

    #[Route('', name: 'app_setting_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        // Ensure all default keys exist in the database
        foreach (self::DEFAULTS as $cle => $info) {
            if (!$this->settingRepository->findByKey($cle)) {
                $setting = new Setting();
                $setting->setCle($cle);
                $setting->setValeur($info['default']);
                $setting->setDescription($info['description']);
                $this->em->persist($setting);
            }
        }
        $this->em->flush();

        /** @var Setting[] $settings */
        $settings    = $this->settingRepository->findAll();
        $settingsMap = [];
        foreach ($settings as $setting) {
            $settingsMap[$setting->getCle()] = $setting;
        }

        // Build a dynamic form with one field per setting
        $formBuilder = $this->createFormBuilder([]);
        foreach (self::DEFAULTS as $cle => $info) {
            $setting = $settingsMap[$cle] ?? null;
            $isLong  = in_array($cle, ['invoice_footer', 'quote_footer'], true);
            $type    = $isLong ? TextareaType::class : TextType::class;

            $options = [
                'label'    => $info['label'],
                'required' => false,
                'data'     => $setting?->getValeur() ?? $info['default'],
                'attr'     => $isLong
                    ? ['class' => 'form-control', 'rows' => 3]
                    : ['class' => 'form-control'],
            ];

            $formBuilder->add($cle, $type, $options);
        }

        $formBuilder->add('submit', SubmitType::class, [
            'label' => 'Enregistrer les paramètres',
            'attr'  => ['class' => 'btn btn-primary'],
        ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            foreach (self::DEFAULTS as $cle => $info) {
                $setting = $settingsMap[$cle] ?? null;
                if (!$setting) {
                    $setting = new Setting();
                    $setting->setCle($cle);
                    $setting->setDescription($info['description']);
                    $this->em->persist($setting);
                }
                $setting->setValeur($data[$cle] ?? '');
            }
            $this->em->flush();

            $this->addFlash('success', 'Paramètres enregistrés avec succès.');

            return $this->redirectToRoute('app_setting_index');
        }

        return $this->render('setting/index.html.twig', [
            'form'     => $form,
            'defaults' => self::DEFAULTS,
        ]);
    }

    /**
     * Exports all settings as a downloadable JSON file.
     */
    #[Route('/export', name: 'app_setting_export', methods: ['GET'])]
    public function export(): JsonResponse
    {
        $settings = $this->settingRepository->findAll();
        $data     = [];

        foreach ($settings as $setting) {
            $data[$setting->getCle()] = $setting->getValeur();
        }

        $response = new JsonResponse($data);
        $response->headers->set('Content-Disposition', 'attachment; filename="settings_' . date('Y-m-d') . '.json"');

        return $response;
    }

    /**
     * Resets all settings to their default values.
     */
    #[Route('/reset', name: 'app_setting_reset', methods: ['POST'])]
    public function reset(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reset_settings', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_setting_index');
        }

        foreach (self::DEFAULTS as $cle => $info) {
            $setting = $this->settingRepository->findByKey($cle);
            if ($setting) {
                $setting->setValeur($info['default']);
            } else {
                $setting = new Setting();
                $setting->setCle($cle);
                $setting->setValeur($info['default']);
                $setting->setDescription($info['description']);
                $this->em->persist($setting);
            }
        }

        $this->em->flush();

        $this->addFlash('success', 'Paramètres réinitialisés aux valeurs par défaut.');

        return $this->redirectToRoute('app_setting_index');
    }
}
