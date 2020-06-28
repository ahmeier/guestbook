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
     * @Route("/admin", name="admin")
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
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Conference');
        yield MenuItem::linkToCrud('Conferences', 'fa fa-tags', Conference::class);
        yield MenuItem::linkToCrud('Add Conference', 'fa fa-handshake-o', Conference::class)
            ->setAction('new');

        yield MenuItem::linkToCrud('Comments', 'fa fa-tag', Comment::class);
        yield MenuItem::linkToCrud('Add Comment', 'fa fa-comment', Comment::class)
            ->setAction('new');
    }
}
