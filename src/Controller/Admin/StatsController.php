<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\VillaRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/stats')]
class StatsController extends AbstractController
{
    #[Route('/', name: 'app_admin_stats')]
    public function index(
        UserRepository $userRepository,
        VillaRepository $villaRepository,
        ReservationRepository $reservationRepository
    ): Response {
        // Statistiques générales
        $totalUsers = $userRepository->count([]);
        $totalActiveVillas = $villaRepository->count(['isActive' => true]);
        $totalRevenue = $reservationRepository->getTotalRevenue();
        
        // Données pour les graphiques
        $monthlyRevenue = $reservationRepository->getMonthlyRevenue();
        $monthlyRegistrations = $userRepository->getMonthlyRegistrations();
        $villasByLocation = $villaRepository->getVillasByLocation();
        
        return $this->render('admin/stats/index.html.twig', [
            'totalUsers' => $totalUsers,
            'totalActiveVillas' => $totalActiveVillas,
            'totalRevenue' => $totalRevenue,
            'monthlyRevenue' => json_encode($monthlyRevenue),
            'monthlyRegistrations' => json_encode($monthlyRegistrations),
            'villasByLocation' => json_encode($villasByLocation),
        ]);
    }
}
