<?php

namespace App\EventSubscriber;

use App\Repository\ConferenceRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
    /** @var Environment */
    private Environment $twig;

    /** @var ConferenceRepository */
    private ConferenceRepository $repository;

    /**
     * TwigEventSubscriber constructor.
     * @param Environment $twig
     * @param ConferenceRepository $repository
     */
    public function __construct(Environment $twig, ConferenceRepository $repository)
    {
        $this->twig = $twig;
        $this->repository = $repository;
    }

    /**
     * @param ControllerEvent $event
     */
    public function onKernelController(ControllerEvent $event): void
    {
       $this->twig->addGlobal('conferences', $this->repository->findAll());
    }

    /**
     * @return array|string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
