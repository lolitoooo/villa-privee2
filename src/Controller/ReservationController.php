<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\ReservationOption;
use App\Entity\Villa;
use App\Entity\Option;
use App\Repository\OptionRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
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
        Stripe::setApiKey($this->stripeSecretKey);
    }

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

    #[Route('/villa/{id}/check-availability', name: 'app_reservation_check_availability', methods: ['POST'])]
    public function checkAvailability(
        Villa $villa,
        Request $request,
        ReservationRepository $reservationRepository,
        OptionRepository $optionRepository
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

        // Calculer le prix des options si elles sont présentes
        if (isset($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $optionData) {
                $option = $optionRepository->find($optionData['id']);
                if ($option) {
                    $totalPrice += $option->getPrice() * $optionData['quantity'];
                }
            }
        }

        return $this->json([
            'available' => count($overlappingReservations) === 0,
            'totalPrice' => $totalPrice,
            'nights' => $nights
        ]);
    }

    #[Route('/create-payment-intent/{id}', name: 'app_reservation_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request, Villa $villa, OptionRepository $optionRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $startDate = new \DateTime($data['startDate']);
        $endDate = new \DateTime($data['endDate']);
        
        // Calculer le nombre de nuits
        $interval = $startDate->diff($endDate);
        $nights = $interval->days;
        
        // Calculer le prix de base
        $basePrice = $villa->getPrice() * $nights;
        
        // Calculer le prix des options
        $optionsPrice = 0;
        $selectedOptions = [];
        
        foreach ($data['options'] as $optionData) {
            $option = $optionRepository->find($optionData['id']);
            if (!$option) {
                return new JsonResponse(['error' => 'Option non trouvée'], 400);
            }
            
            $optionsPrice += $option->getPrice() * $optionData['quantity'];
            $selectedOptions[] = [
                'option' => $option,
                'quantity' => $optionData['quantity']
            ];
        }
        
        $totalAmount = ($basePrice + $optionsPrice) * 100; // Conversion en centimes pour Stripe
        
        try {
            // Créer l'intention de paiement Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => $totalAmount,
                'currency' => 'eur',
                'metadata' => [
                    'villa_id' => $villa->getId(),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'options' => json_encode($data['options'])
                ]
            ]);
            
            // Créer la réservation en base de données
            $reservation = new Reservation();
            $reservation->setVilla($villa);
            $reservation->setUser($this->getUser());
            $reservation->setStartDate($startDate);
            $reservation->setEndDate($endDate);
            $reservation->setTotalPrice($totalAmount / 100);
            $reservation->setStatus('pending');
            $reservation->setStripePaymentIntentId($paymentIntent->id);
            
            // Ajouter les options à la réservation
            foreach ($selectedOptions as $selectedOption) {
                $reservationOption = new ReservationOption();
                $reservationOption->setReservation($reservation);
                $reservationOption->setOption($selectedOption['option']);
                $reservationOption->setQuantity($selectedOption['quantity']);
                $em->persist($reservationOption);
            }
            
            $em->persist($reservation);
            $em->flush();
            
            return new JsonResponse([
                'clientSecret' => $paymentIntent->client_secret,
                'reservationId' => $reservation->getId()
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/webhook', name: 'app_reservation_webhook', methods: ['POST'])]
    public function handleStripeWebhook(Request $request, EntityManagerInterface $em, ReservationRepository $reservationRepository): Response
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $request->headers->get('Stripe-Signature');
        $endpoint_secret = $this->getParameter('stripe_webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            return new Response('Webhook Error: Invalid payload', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Webhook Error: Invalid signature', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            
            $reservation = $reservationRepository->findOneBy([
                'stripePaymentIntentId' => $paymentIntent->id
            ]);
            
            if ($reservation) {
                $reservation->setStatus('confirmed');
                $em->flush();
            }
        }

        return new Response('Webhook handled', 200);
    }

    #[Route('/success', name: 'app_reservation_success', methods: ['GET'])]
    public function success(): Response
    {
        return $this->render('reservation/success.html.twig');
    }

    #[Route('/villa/{id}/create', name: 'app_reservation_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Villa $villa,
        Request $request,
        ReservationRepository $reservationRepository,
        OptionRepository $optionRepository,
        \Symfony\Component\Mailer\MailerInterface $mailer
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
            return $this->json(['error' => 'La villa n\'est pas disponible pour ces dates'], Response::HTTP_BAD_REQUEST);
        }

        $nights = $startDate->diff($endDate)->days;
        $totalPrice = $villa->getPrice() * $nights;

        $reservation = new Reservation();
        $reservation->setVilla($villa);
        $reservation->setUser($this->getUser());
        $reservation->setStartDate($startDate);
        $reservation->setEndDate($endDate);
        $reservation->setStatus('pending');
        $reservation->setTotalPrice($totalPrice);

        // Ajouter les options à la réservation
        if (isset($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $optionData) {
                $option = $optionRepository->find($optionData['id']);
                if ($option) {
                    $reservationOption = new ReservationOption();
                    $reservationOption->setOption($option);
                    $reservationOption->setQuantity($optionData['quantity']);
                    $reservationOption->setReservation($reservation);
                    $totalPrice += $option->getPrice() * $optionData['quantity'];
                    $this->entityManager->persist($reservationOption);
                }
            }
        }

        $reservation->setTotalPrice($totalPrice);
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        // Créer la session de paiement Stripe
        $lineItems = [
            [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $villa->getTitle(),
                        'description' => "Séjour du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}",
                    ],
                    'unit_amount' => (int)($villa->getPrice() * 100),
                ],
                'quantity' => $nights,
            ]
        ];

        // Ajouter les options comme lignes séparées
        foreach ($reservation->getReservationOptions() as $reservationOption) {
            $option = $reservationOption->getOption();
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $option->getName(),
                        'description' => $option->getDescription(),
                    ],
                    'unit_amount' => (int)($option->getPrice() * 100),
                ],
                'quantity' => $reservationOption->getQuantity(),
            ];
        }

        $checkoutSession = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_reservation_success', [
                'id' => $reservation->getId(),
            ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_reservation_cancel', [
                'id' => $reservation->getId(),
            ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            'client_reference_id' => $reservation->getId(),
        ]);

        return $this->json([
            'checkoutUrl' => $checkoutSession->url,
        ]);
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
