<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Villa;
use App\Entity\VillaImage;
use App\Form\AdminUserType;
use App\Form\VillaType;
use App\Repository\UserRepository;
use App\Repository\VillaRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/villas/{id}/toggle-status', name: 'app_admin_villas_toggle_status', methods: ['POST'])]
    public function toggleVillaStatus(
        Villa $villa,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid('toggle-status'.$villa->getId(), $request->request->get('_token'))) {
            return $this->json(['message' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        $villa->setIsActive(!$villa->isIsActive());
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'isActive' => $villa->isIsActive()
        ]);
    }
    #[Route('/', name: 'app_admin_index')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }
    #[Route('/villas', name: 'app_admin_villas')]
    public function villas(
        VillaRepository $villaRepository,
        Request $request
    ): Response {
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $villas = $villaRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );

        $total = $villaRepository->count([]);

        return $this->render('admin/villas.html.twig', [
            'villas' => $villas,
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit),
        ]);
    }

    #[Route('/villas/new', name: 'app_admin_villas_new')]
    public function newVilla(
        Request $request,
        VillaRepository $villaRepository,
        SluggerInterface $slugger
    ): Response {
        $villa = new Villa();
        $form = $this->createForm(VillaType::class, $villa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $villa->setCreatedAt(new \DateTimeImmutable());
            $villa->setSlug($slugger->slug($villa->getTitle())->lower());
            $villa->setOwner($this->getUser());

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

            $villaRepository->save($villa);

            $this->addFlash('success', 'L\'annonce a été créée avec succès.');
            return $this->redirectToRoute('app_admin_villas');
        }

        return $this->render('admin/villa_form.html.twig', [
            'form' => $form->createView(),
            'villa' => $villa,
        ]);
    }

    #[Route('/villas/{id}/toggle-active', name: 'app_admin_villas_toggle_active', methods: ['POST'])]
    public function toggleVillaActive(
        Villa $villa,
        VillaRepository $villaRepository
    ): Response {
        $villa->setIsActive(!$villa->isIsActive());
        $villaRepository->save($villa);

        $status = $villa->isIsActive() ? 'activée' : 'mise en pause';
        $this->addFlash('success', sprintf("L'annonce a été %s avec succès.", $status));

        return $this->redirectToRoute('app_admin_villas');
    }

    #[Route('/villas/{id}/edit', name: 'app_admin_villas_edit')]
    public function editVilla(
        Villa $villa,
        Request $request,
        VillaRepository $villaRepository,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(VillaType::class, $villa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $villa->setSlug($slugger->slug($villa->getTitle())->lower());
            $villaRepository->save($villa);

            $this->addFlash('success', 'L\'annonce a été modifiée avec succès.');
            return $this->redirectToRoute('app_admin_villas');
        }

        return $this->render('admin/villa_form.html.twig', [
            'form' => $form->createView(),
            'villa' => $villa,
        ]);
    }

    #[Route('/villas/{id}/delete', name: 'app_admin_villas_delete', methods: ['POST'])]
    public function deleteVilla(
        Villa $villa,
        Request $request,
        VillaRepository $villaRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $villa->getId(), $request->request->get('_token'))) {
            $villaRepository->remove($villa);
            $this->addFlash('success', 'L\'annonce a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_villas');
    }

    #[Route('/villas/{id}/toggle', name: 'app_admin_villas_toggle', methods: ['POST'])]
    public function toggleVilla(
        Villa $villa,
        VillaRepository $villaRepository
    ): Response {
        $villa->setIsActive(!$villa->isIsActive());
        $villaRepository->save($villa);

        return $this->json([
            'isActive' => $villa->isIsActive()
        ]);
    }
    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/users/new', name: 'app_admin_users_new')]
    public function new(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $form->get('roles')->getData();
            $user->setRoles([$role]);

            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_users_edit')]
    public function edit(
        User $user,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(AdminUserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $form->get('roles')->getData();
            $user->setRoles([$role]);

            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $plainPassword)
                );
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function delete(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/toggle-ban', name: 'app_admin_users_toggle_ban', methods: ['POST'])]
    public function toggleBan(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('ban'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsBanned(!$user->isBanned());
            $entityManager->flush();
            
            $status = $user->isBanned() ? 'banni' : 'débanni';
            $this->addFlash('success', "L'utilisateur a été $status avec succès.");
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
