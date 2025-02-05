<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

class AccessDeniedListener implements EventSubscriberInterface
{
    private FlashBagInterface $flashBag;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        RequestStack $requestStack
    ) {
        $this->flashBag = $requestStack->getSession()->getFlashBag();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof AccessDeniedException) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Si l'utilisateur est connecté
        if ($this->security->getUser()) {
            if (str_starts_with($route, 'app_admin')) {
                $this->flashBag->add('error', 'Accès refusé. Cette section est réservée aux administrateurs.');
                $response = new RedirectResponse($this->urlGenerator->generate('app_home'));
            } elseif (str_starts_with($route, 'app_villa') && !str_starts_with($route, 'app_villa_index') && !str_starts_with($route, 'app_villa_show')) {
                $this->flashBag->add('error', 'Accès refusé. Cette section est réservée aux propriétaires.');
                $response = new RedirectResponse($this->urlGenerator->generate('app_home'));
            } else {
                $this->flashBag->add('error', 'Accès refusé.');
                $response = new RedirectResponse($this->urlGenerator->generate('app_home'));
            }
        } else {
            $this->flashBag->add('error', 'Veuillez vous connecter pour accéder à cette page.');
            $response = new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        $event->setResponse($response);
    }
}
