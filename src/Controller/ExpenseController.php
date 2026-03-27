<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Form\ExpenseType;
use App\Repository\ExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/expenses')]
class ExpenseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ExpenseRepository $expenseRepository,
    ) {}

    #[Route('', name: 'app_expense_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $projectId = $request->query->get('project', '');
        $categorie = $request->query->get('categorie', '');

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Expense::class, 'e')
            ->join('e.project', 'p')
            ->orderBy('e.date', 'DESC');

        if ($projectId) {
            $qb->andWhere('p.id = :projectId')->setParameter('projectId', (int) $projectId);
        }

        if ($categorie) {
            $qb->andWhere('e.categorie = :categorie')->setParameter('categorie', $categorie);
        }

        $expenses = $qb->getQuery()->getResult();

        $totalMontant = array_reduce(
            $expenses,
            fn (float $carry, Expense $e) => $carry + (float) $e->getMontant(),
            0.0
        );

        return $this->render('expense/index.html.twig', [
            'expenses'     => $expenses,
            'categories'   => Expense::CATEGORIES,
            'projectId'    => (int) $projectId,
            'categorie'    => $categorie,
            'totalMontant' => round($totalMontant, 2),
        ]);
    }

    #[Route('/new', name: 'app_expense_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $expense = new Expense();
        $form    = $this->createForm(ExpenseType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($expense);
            $this->em->flush();

            $this->addFlash('success', 'Dépense créée avec succès.');

            return $this->redirectToRoute('app_expense_index');
        }

        return $this->render('expense/new.html.twig', [
            'expense' => $expense,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_expense_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Expense $expense): Response
    {
        return $this->render('expense/show.html.twig', [
            'expense' => $expense,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_expense_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Expense $expense): Response
    {
        $form = $this->createForm(ExpenseType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Dépense modifiée avec succès.');

            return $this->redirectToRoute('app_expense_show', ['id' => $expense->getId()]);
        }

        return $this->render('expense/edit.html.twig', [
            'expense' => $expense,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_expense_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Expense $expense): Response
    {
        if ($this->isCsrfTokenValid('delete_expense_' . $expense->getId(), $request->request->get('_token'))) {
            $this->em->remove($expense);
            $this->em->flush();
            $this->addFlash('success', 'Dépense supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_expense_index');
    }
}
