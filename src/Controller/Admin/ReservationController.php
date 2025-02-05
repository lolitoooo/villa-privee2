<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\InvoiceGenerator;

#[Route('/admin/reservations')]
#[IsGranted('ROLE_ADMIN')]
class ReservationController extends AbstractController
{
    public function __construct(
        private InvoiceGenerator $invoiceGenerator
    ) {}
    #[Route('', name: 'app_admin_reservations_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_reservations_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('admin/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_reservations_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_reservations_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/status', name: 'app_admin_reservations_status', methods: ['POST'])]
    public function updateStatus(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $status = $request->request->get('status');
        if (!in_array($status, ['pending', 'confirmed', 'canceled'])) {
            throw $this->createNotFoundException('Statut invalide.');
        }

        $reservation->setStatus($status);

        // Si la réservation est confirmée et n'a pas encore de facture, on en génère une
        if ($status === 'confirmed' && !$reservation->getInvoice()) {
            $this->invoiceGenerator->generatePdf($reservation);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Le statut de la réservation a été mis à jour avec succès.');

        return $this->redirectToRoute('app_admin_reservations_show', ['id' => $reservation->getId()], Response::HTTP_SEE_OTHER);
    }
}