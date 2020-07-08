<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Message\Comment\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AdminController
{
    /** @var Environment */
    private Environment $twig;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /** @var MessageBusInterface */
    private MessageBusInterface $bus;

    /**
     * AdminController constructor.
     * @param Environment $twig
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface $bus
     *
     */
    public function __construct(Environment $twig, EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    /**
     * @Route(path="/admin/comment/review/{id}", name="review_comment")
     *
     * @param Request $request
     * @param Comment $comment
     * @param Registry $registry
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function reviewComment(Request $request, Comment $comment, Registry $registry): Response
    {
        $accepted = !$request->query->get('reject');

        $machine = $registry->get($comment);
        if($machine->can($comment, 'publish')) {
            $transition = $accepted ? 'publish' : 'reject';
        } elseif($machine->can($comment, 'publish_ham')) {
            $transition = $accepted ? 'publish_ham' : 'reject_ham';
        } else {
            return new Response('Comment already reviewed or not in the correct state!');
        }

        $machine->apply($comment, $transition);
        $this->entityManager->flush();

        if($accepted) {
            $this->bus->dispatch(new CommentMessage($comment->getId()));
        }

        $html = $this->twig->render('admin/review.html.twig', [
            'transition' => $transition,
            'comment' => $comment
        ]);

        return new Response($html);
    }
}