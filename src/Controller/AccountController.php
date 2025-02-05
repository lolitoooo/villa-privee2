<?php

namespace App\Controller;

use App\Form\UserEditType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\InvoiceGenerator;
use App\Entity\Reservation;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/account')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    public function __construct(
        private InvoiceGenerator $invoiceGenerator
    ) {}
    #[Route('', name: 'app_account')]
    public function index(): Response
    {
        return $this->render('account/index.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/edit', name: 'app_account_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/change-password', name: 'app_account_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('newPassword')->getData()
                )
            );

            $entityManager->flush();
            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/change_password.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/reservations', name: 'app_account_reservations')]
    public function reservations(): Response
    {
        return $this->render('account/reservations.html.twig', [
            'reservations' => $this->getUser()->getReservations()
        ]);
    }

    #[Route('/delete', name: 'app_account_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('delete-account', $request->request->get('_token'))) {

            $entityManager->remove($user);
            $entityManager->flush();
            
            $this->container->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();
            
            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la suppression de votre compte.');
        return $this->redirectToRoute('app_account');
    }

    #[Route('/reservations/{id}/invoice', name: 'app_account_reservation_invoice')]
    public function downloadInvoice(Reservation $reservation): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que la réservation appartient à l'utilisateur
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette facture.');
        }

        $pdfContent = $this->invoiceGenerator->generatePdf($reservation);

        $response = new Response($pdfContent);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'facture-' . $reservation->getId() . '.pdf'
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }

    #[Route('/reservations/{id}/invoice/view', name: 'app_account_reservation_invoice_view')]
    public function viewInvoice(Reservation $reservation): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que la réservation appartient à l'utilisateur
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette facture.');
        }

        $pdfContent = $this->invoiceGenerator->generatePdf($reservation);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }
}
