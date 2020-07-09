<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Conference;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/easyadmin", name="easyadmin")
     */
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Guestbook');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Main');
        yield MenuItem::linkToUrl('Homepage', 'fa fa-home', '/');
        yield MenuItem::linktoDashboard('Back to Dashboard', 'fa fa-admin');
//        yield MenuItem::linkToLogout('Logout', 'fa fa-exit');

        yield MenuItem::section('Conferences');
        yield MenuItem::linkToCrud('List Conferences', 'fa fa-tags', Conference::class);

        yield MenuItem::section('Comments');
        yield MenuItem::linkToCrud('List Comments', 'fa fa-tag', Comment::class);
    }
}
