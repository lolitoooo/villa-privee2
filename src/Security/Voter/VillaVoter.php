<?php

namespace App\Security\Voter;

use App\Entity\Villa;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;

class VillaVoter extends Voter
{
    // Définition des actions possibles
    const VIEW = 'VILLA_VIEW';
    const EDIT = 'VILLA_EDIT';
    const DELETE = 'VILLA_DELETE';
    const ADD_REVIEW = 'VILLA_ADD_REVIEW';
    const MANAGE_REVIEWS = 'VILLA_MANAGE_REVIEWS';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::ADD_REVIEW,
            self::MANAGE_REVIEWS
        ]) && ($subject instanceof Villa);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si l'utilisateur n'est pas connecté, pas d'accès
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Villa $villa */
        $villa = $subject;

        // Vérifier si l'utilisateur est un admin
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }



        return match($attribute) {
            self::VIEW => $this->security->isGranted('ROLE_USER'), // Seuls les utilisateurs connectés peuvent voir les villas
            self::EDIT => $this->canEdit($villa, $user),
            self::DELETE => $this->canDelete($villa, $user),
            self::ADD_REVIEW => $this->canAddReview($villa, $user),
            self::MANAGE_REVIEWS => $this->canManageReviews($villa, $user),
            default => false,
        };
    }

    private function canEdit(Villa $villa, UserInterface $user): bool
    {
        // Seul le propriétaire peut éditer sa villa
        return $villa->getOwner() === $user && $this->security->isGranted('ROLE_OWNER');
    }

    private function canDelete(Villa $villa, UserInterface $user): bool
    {
        // Même logique que pour l'édition
        return $this->canEdit($villa, $user);
    }

    private function canAddReview(Villa $villa, UserInterface $user): bool
    {
        // Un utilisateur ne peut pas commenter sa propre villa
        // et doit avoir le rôle utilisateur minimum
        return $villa->getOwner() !== $user 
            && $this->security->isGranted('ROLE_USER');
    }

    private function canManageReviews(Villa $villa, UserInterface $user): bool
    {
        // Seul le propriétaire peut gérer les avis de sa villa
        return $villa->getOwner() === $user && $this->security->isGranted('ROLE_OWNER');
    }
}
