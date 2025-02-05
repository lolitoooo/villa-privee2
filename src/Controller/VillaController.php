<?php

namespace App\Controller;

use App\Entity\Villa;
use App\Entity\VillaImage;
use App\Entity\VillaReview;
use App\Form\VillaType;
use App\Form\VillaReviewType;
use App\Repository\VillaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/villas')]
class VillaController extends AbstractController
{
    #[Route('/', name: 'app_villa_index', methods: ['GET'])]
    public function index(Request $request, VillaRepository $villaRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $filters = [
            'q' => $request->query->get('q'),
            'max_price' => $request->query->get('max_price'),
            'capacity' => $request->query->get('capacity'),
            'bedrooms' => $request->query->get('bedrooms'),
        ];

        $result = $villaRepository->findByFilters($filters, $page);

        return $this->render('villa/index.html.twig', [
            'villas' => $result['villas'],
            'currentPage' => $result['currentPage'],
            'totalPages' => $result['totalPages'],
            'totalItems' => $result['totalItems'],
            'filters' => $filters,
        ]);
    }

    #[Route('/mes-villas', name: 'app_villa_my_listings', methods: ['GET'])]
    #[IsGranted('ROLE_OWNER')]
    public function myListings(VillaRepository $villaRepository): Response
    {
        return $this->render('villa/my_listings.html.twig', [
            'villas' => $villaRepository->findByOwner($this->getUser()->getId()),
        ]);
    }

    #[Route('/nouvelle', name: 'app_villa_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OWNER')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $villa = new Villa();
        $form = $this->createForm(VillaType::class, $villa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $villa->setOwner($this->getUser());
            $villa->setSlug($slugger->slug($villa->getTitle())->lower());
            
            // Gérer les images
            $images = $form->get('imageFiles')->getData();
            if ($images) {
                foreach ($images as $image) {
                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                    try {
                        $image->move(
                            $this->getParameter('villa_images_directory'),
                            $newFilename
                        );

                        $villaImage = new VillaImage();
                        $villaImage->setFilename($newFilename);
                        $villa->addImage($villaImage);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
                    }
                }
            }

            $entityManager->persist($villa);
            $entityManager->flush();

            return $this->redirectToRoute('app_villa_my_listings', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('villa/new.html.twig', [
            'villa' => $villa,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_villa_show', methods: ['GET'])]
    public function show(Villa $villa, ParameterBagInterface $params): Response
    {
        $this->denyAccessUnlessGranted('VILLA_VIEW', $villa);

        $review = new VillaReview();
        $reviewForm = $this->createForm(VillaReviewType::class, $review);

        return $this->render('villa/show.html.twig', [
            'villa' => $villa,
            'reviewForm' => $reviewForm,
            'stripe_public_key' => $params->get('stripe_public_key'),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_villa_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Villa $villa, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('VILLA_EDIT', $villa);

        $form = $this->createForm(VillaType::class, $villa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer les nouvelles images
            $images = $form->get('imageFiles')->getData();
            if ($images) {
                foreach ($images as $image) {
                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                    try {
                        $image->move(
                            $this->getParameter('villa_images_directory'),
                            $newFilename
                        );

                        $villaImage = new VillaImage();
                        $villaImage->setFilename($newFilename);
                        $villa->addImage($villaImage);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
                    }
                }
            }

            $villa->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            return $this->redirectToRoute('app_villa_my_listings', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('villa/edit.html.twig', [
            'villa' => $villa,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_villa_toggle', methods: ['POST'])]
    public function toggle(Request $request, Villa $villa, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('VILLA_EDIT', $villa);

        if ($this->isCsrfTokenValid('toggle'.$villa->getId(), $request->request->get('_token'))) {
            $villa->setIsActive(!$villa->isIsActive());
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_villa_my_listings', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/supprimer-image/{imageId}', name: 'app_villa_delete_image', methods: ['POST'])]
    public function deleteImage(Request $request, Villa $villa, int $imageId, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('VILLA_EDIT', $villa);

        if ($this->isCsrfTokenValid('delete-image'.$imageId, $request->request->get('_token'))) {
            $image = $entityManager->getRepository(VillaImage::class)->find($imageId);
            
            if ($image && $image->getVilla() === $villa) {
                // Supprimer le fichier physique
                $imagePath = $this->getParameter('villa_images_directory').'/'.$image->getFilename();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                
                $entityManager->remove($image);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('app_villa_edit', ['id' => $villa->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_villa_delete', methods: ['POST'])]
    #[IsGranted('ROLE_OWNER')]
    public function delete(Request $request, Villa $villa, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$villa->getId(), $request->request->get('_token'))) {
            // Vérifier que l'utilisateur est bien le propriétaire
            if ($villa->getOwner() !== $this->getUser()) {
                throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette villa.');
            }
            
            $entityManager->remove($villa);
            $entityManager->flush();
            
            $this->addFlash('success', 'La villa a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_villa_my_listings', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/avis', name: 'app_villa_add_review', methods: ['POST'])]
    public function addReview(Request $request, Villa $villa, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('VILLA_ADD_REVIEW', $villa);

        $review = new VillaReview();
        $form = $this->createForm(VillaReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setVilla($villa);
            $review->setUser($this->getUser());
            $review->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Votre avis a été ajouté avec succès.');
        } else {
            $this->addFlash('error', 'Il y a eu une erreur lors de l\'ajout de votre avis.');
        }

        return $this->redirectToRoute('app_villa_show', ['id' => $villa->getId()], Response::HTTP_SEE_OTHER);
    }
}
