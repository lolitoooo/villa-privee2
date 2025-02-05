<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Villa;
use App\Repository\FavoriteRepository;
use App\Repository\VillaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class FavoriteController extends AbstractController
{
    #[Route('/favorites', name: 'app_favorites')]
    public function index(): Response
    {
        return $this->render('favorite/index.html.twig', [
            'favorites' => $this->getUser()->getFavorites(),
        ]);
    }

    #[Route('/api/favorites/{id}', name: 'app_favorite_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggle(
        Villa $villa,
        FavoriteRepository $favoriteRepository
    ): JsonResponse {
        $user = $this->getUser();
        $favorite = $favoriteRepository->findByUserAndVilla($user, $villa);

        if ($favorite) {
            $favoriteRepository->remove($favorite);
            return $this->json([
                'isFavorite' => false,
                'message' => 'Villa retirée des favoris'
            ]);
        }

        $favorite = new Favorite();
        $favorite->setUser($user)
                ->setVilla($villa);
        
        $favoriteRepository->save($favorite);

        return $this->json([
            'isFavorite' => true,
            'message' => 'Villa ajoutée aux favoris'
        ]);
    }

    #[Route('/api/favorites/{id}/status', name: 'app_favorite_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function status(
        Villa $villa,
        FavoriteRepository $favoriteRepository
    ): JsonResponse {
        $favorite = $favoriteRepository->findByUserAndVilla($this->getUser(), $villa);
        
        return $this->json([
            'isFavorite' => $favorite !== null
        ]);
    }
}
