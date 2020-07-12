<?php

namespace App\MessageHandler\Comment;

use App\Message\Comment\CommentMessage;
use App\Notification\CommentReviewNotification;
use App\Repository\CommentRepository;
use App\Utils\ImageOptimizer;
use App\Utils\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface as MailerTransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface as HttpTransportExceptionInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    /** @var CommentRepository */
    private CommentRepository $repository;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /** @var ImageOptimizer */
    private ImageOptimizer $optimizer;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var MessageBusInterface */
    private MessageBusInterface $bus;

    /** @var NotifierInterface */
    private NotifierInterface $notifier;

    /** @var SpamChecker */
    private SpamChecker $spamChecker;

    /** @var WorkflowInterface */
    private WorkflowInterface $workflow;

    /** @var string */
    private string $adminEmail;

    /** @var string */
    private string $photoDir;

    /**
     * CommentMessageHandler constructor.
     * @param CommentRepository $repository
     * @param EntityManagerInterface $entityManager
     * @param ImageOptimizer $optimizer
     * @param LoggerInterface $logger
     * @param NotifierInterface $notifier
     * @param MessageBusInterface $bus
     * @param SpamChecker $spamChecker
     * @param WorkflowInterface $commentStateMachine
     * @param string $adminEmail
     * @param string $photoDir
     */
    public function __construct(
        CommentRepository $repository,
        EntityManagerInterface $entityManager,
        ImageOptimizer $optimizer,
        LoggerInterface $logger,
        NotifierInterface $notifier,
        MessageBusInterface $bus,
        SpamChecker $spamChecker,
        WorkflowInterface $commentStateMachine,
        string $photoDir
    )
    {

        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->bus = $bus;
        $this->workflow = $commentStateMachine;
        $this->notifier = $notifier;
        $this->optimizer = $optimizer;
        $this->photoDir = $photoDir;
    }

    /**
     * @param CommentMessage $message
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws HttpTransportExceptionInterface
     */
    public function __invoke(CommentMessage $message): void
    {
        $comment = $this->repository->find($message->getId());
        if (!$comment) {
            return;
        }

        if ($this->workflow->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = 'accept';
            if (2 === $score) {
                $transition = 'reject_spam';
            } elseif (1 === $score) {
                $transition = 'might_be_spam';
            }
            $this->workflow->apply($comment, $transition);
            $this->entityManager->flush();
            $this->bus->dispatch($message);

        } elseif ($this->workflow->can($comment, 'publish') ||
            $this->workflow->can($comment, 'publish_ham')) {
            $notification = new CommentReviewNotification($comment, $message->getReviewUrl());

            $this->notifier->send($notification, ...$this->notifier->getAdminRecipients());
        } elseif ($this->workflow->can($comment, 'optimize')) {
            if($comment->getPhotoFilename()) {
                $this->optimizer->resize($this->photoDir . '/' . $comment->getPhotoFilename());
            }
                $this->workflow->apply($comment, 'optimize');
                $this->entityManager->flush();
        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', [
                'comment' => $comment->getId(),
                'state' => $comment->getState()
            ]);
        }
    }
}