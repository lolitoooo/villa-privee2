<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class BannedUserListener implements EventSubscriberInterface
{
    private array $allowedRoutes = [
        'app_home',
        'app_account',
        'app_logout'
    ];

    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Ne rien faire si ce n'est pas la requête principale
        if (!$event->isMainRequest()) {
            return;
        }

        // Récupérer l'utilisateur connecté
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Vérifier si l'utilisateur est banni
        if (!$user->isBanned()) {
            return;
        }

        // Récupérer la route actuelle
        $route = $request->attributes->get('_route');
        
        // Si la route n'est pas dans la liste des routes autorisées
        if (!in_array($route, $this->allowedRoutes)) {
            // Rediriger vers la page d'accueil
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_home')));
        }
    }
}
