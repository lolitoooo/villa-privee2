<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Villa;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Stripe\StripeClient;
use Stripe\Service\Checkout\SessionService;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/villa/{id}/unavailable-dates', name: 'app_reservation_unavailable_dates', methods: ['GET'])]
    public function getUnavailableDates(
        Villa $villa,
        ReservationRepository $reservationRepository
    ): JsonResponse {
        $reservations = $reservationRepository->findBy(['villa' => $villa, 'status' => ['confirmed', 'pending']]);
        
        $unavailableDates = [];
        foreach ($reservations as $reservation) {
            $current = clone $reservation->getStartDate();
            while ($current <= $reservation->getEndDate()) {
                $unavailableDates[] = $current->format('Y-m-d');
                $current->modify('+1 day');
            }
        }
        
        return $this->json(['dates' => $unavailableDates]);
    }
    private string $stripeSecretKey;
    private string $stripePublicKey;
    
    private StripeClient $stripe;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params
    ) {
        $this->stripeSecretKey = $this->params->get('stripe_secret_key');
        $this->stripePublicKey = $this->params->get('stripe_public_key');
        $this->stripe = new StripeClient($this->stripeSecretKey);
    }

    #[Route('/villa/{id}/check-availability', name: 'app_reservation_check_availability', methods: ['POST'])]
    public function checkAvailability(
        Villa $villa,
        Request $request,
        ReservationRepository $reservationRepository
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('check-availability', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['error' => 'Token CSRF invalide'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $startDate = new \DateTime($data['startDate']);
        $endDate = new \DateTime($data['endDate']);
        $startDate->setTime(0, 0);
        $endDate->setTime(0, 0);

        $overlappingReservations = $reservationRepository->findOverlappingReservations(
            $villa,
            $startDate,
            $endDate
        );

        $nights = $startDate->diff($endDate)->days;
        $totalPrice = $villa->getPrice() * $nights;

        return $this->json([
            'available' => count($overlappingReservations) === 0,
            'totalPrice' => $totalPrice,
            'nights' => $nights
        ]);
    }

    #[Route('/villa/{id}/create', name: 'app_reservation_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Villa $villa,
        Request $request,
        ReservationRepository $reservationRepository,
        MailerInterface $mailer
    ): Response {
        if (!$this->isCsrfTokenValid('create-reservation', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['error' => 'Token CSRF invalide'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $startDate = new \DateTime($data['startDate']);
        $endDate = new \DateTime($data['endDate']);
        $startDate->setTime(0, 0);
        $endDate->setTime(0, 0);

        // Vérifier la disponibilité
        $overlappingReservations = $reservationRepository->findOverlappingReservations(
            $villa,
            $startDate,
            $endDate
        );

        if (count($overlappingReservations) > 0) {
            return $this->json(['error' => 'Ces dates ne sont pas disponibles.'], Response::HTTP_BAD_REQUEST);
        }

        // Calculer le prix total
        $nights = $startDate->diff($endDate)->days;
        $totalPrice = $villa->getPrice() * $nights;

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setVilla($villa)
            ->setUser($this->getUser())
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setTotalPrice($totalPrice)
            ->setStatus('pending');

        $reservationRepository->save($reservation);

        // Créer la session Stripe
        $stripeSession = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int)($totalPrice * 100),
                    'product_data' => [
                        'name' => $villa->getTitle(),
                        'description' => "Réservation du " . $startDate->format('d/m/Y') . " au " . $endDate->format('d/m/Y'),
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_reservation_success', [
                'id' => $reservation->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_reservation_cancel', [
                'id' => $reservation->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'customer_email' => $this->getUser()->getEmail(),
        ]);

        $reservation->setStripePaymentId($stripeSession->id);
        $reservationRepository->save($reservation);

        return $this->json([
            'sessionId' => $stripeSession->id
        ]);
    }

    #[Route('/success/{id}', name: 'app_reservation_success')]
    #[IsGranted('ROLE_USER')]
    public function success(
        Reservation $reservation,
        MailerInterface $mailer
    ): Response {
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $reservation->setStatus('confirmed');
        // Générer un numéro de facture unique
        $reservation->setInvoiceNumber(date('Y') . '-' . str_pad($reservation->getId(), 6, '0', STR_PAD_LEFT));
        $this->entityManager->flush();

        // Envoyer l'email de confirmation
        $email = (new TemplatedEmail())
            ->from('noreply@villaprivee.com')
            ->to($reservation->getUser()->getEmail())
            ->subject('Confirmation de votre réservation')
            ->htmlTemplate('emails/reservation_confirmation.html.twig')
            ->context([
                'reservation' => $reservation
            ]);

        $mailer->send($email);

        return $this->redirectToRoute('app_reservation_confirmation', ['id' => $reservation->getId()]);
    }

    #[Route('/cancel/{id}', name: 'app_reservation_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(Reservation $reservation): Response
    {
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $reservation->setStatus('canceled');
        $this->entityManager->flush();

        $this->addFlash('warning', 'Votre réservation a été annulée.');
        return $this->redirectToRoute('app_villa_show', ['id' => $reservation->getVilla()->getId()]);
    }

    #[Route('/confirmation/{id}', name: 'app_reservation_confirmation')]
    #[IsGranted('ROLE_USER')]
    public function confirmation(Reservation $reservation): Response
    {
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('reservation/confirmation.html.twig', [
            'reservation' => $reservation
        ]);
    }
}
