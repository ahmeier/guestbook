<?php

namespace App\MessageHandler\Comment;

use App\Message\Comment\CommentMessage;
use App\Repository\CommentRepository;
use App\Utils\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;
    /**
     * @var SpamChecker
     */
    private SpamChecker $spamChecker;
    /**
     * @var CommentRepository
     */
    private CommentRepository $repository;

    /**
     * CommentMessageHandler constructor.
     * @param EntityManagerInterface $entityManager
     * @param SpamChecker $spamChecker
     * @param CommentRepository $repository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        SpamChecker $spamChecker,
        CommentRepository $repository
    )
    {

        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->repository = $repository;
    }

    /**
     * @param CommentMessage $message
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function __invoke(CommentMessage $message): void
    {
        $comment = $this->repository->find($message->getId());
        if(!$comment) {
            return;
        }

        $comment->setState('spam');

        if(0 === $this->spamChecker->getSpamScore($comment, $message->getContext()))
        {
            $comment->setState('published');
        }

        $this->entityManager->flush();
    }
}