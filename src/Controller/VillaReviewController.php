<?php

namespace App\Controller;

use App\Entity\Villa;
use App\Entity\VillaReview;
use App\Form\VillaReviewType;
use App\Repository\VillaReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/villa/review')]
class VillaReviewController extends AbstractController
{
    #[Route('/{id}/new', name: 'app_villa_review_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Villa $villa,
        Request $request,
        VillaReviewRepository $reviewRepository
    ): Response {
        $review = new VillaReview();
        $review->setVilla($villa);
        $review->setAuthor($this->getUser());

        $form = $this->createForm(VillaReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reviewRepository->save($review);
            $this->addFlash('success', 'Votre avis a été publié avec succès.');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_villa_show', ['id' => $villa->getId()]);
    }

    #[Route('/{id}', name: 'app_villa_review_delete', methods: ['POST'])]
    public function delete(
        VillaReview $review,
        Request $request,
        VillaReviewRepository $reviewRepository
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN') && $review->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$review->getId(), $request->request->get('_token'))) {
            $villa = $review->getVilla();
            $reviewRepository->remove($review);
            $this->addFlash('success', 'L\'avis a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_villa_show', ['id' => $villa->getId()]);
    }
}
