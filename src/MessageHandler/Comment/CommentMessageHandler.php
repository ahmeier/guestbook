<?php

namespace App\MessageHandler\Comment;

use App\Message\Comment\CommentMessage;
use App\Repository\CommentRepository;
use App\Utils\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
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

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var MailerInterface */
    private MailerInterface $mailer;

    /** @var MessageBusInterface */
    private MessageBusInterface $bus;

    /** @var SpamChecker */
    private SpamChecker $spamChecker;

    /** @var WorkflowInterface */
    private WorkflowInterface $workflow;

    /** @var string */
    private string $adminEmail;

    /**
     * CommentMessageHandler constructor.
     * @param CommentRepository $repository
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @param MailerInterface $mailer
     * @param MessageBusInterface $bus
     * @param SpamChecker $spamChecker
     * @param WorkflowInterface $commentStateMachine
     * @param string $adminEmail
     */
    public function __construct(
        CommentRepository $repository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        MailerInterface $mailer,
        MessageBusInterface $bus,
        SpamChecker $spamChecker,
        WorkflowInterface $commentStateMachine,
        string $adminEmail
    )
    {

        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->bus = $bus;
        $this->workflow = $commentStateMachine;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    /**
     * @param CommentMessage $message
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws HttpTransportExceptionInterface
     * @throws MailerTransportExceptionInterface
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
            $this->mailer->send((new NotificationEmail())
                ->subject('')
                ->htmlTemplate('email/comment_notification.html.twig')
                ->from($this->adminEmail)
                ->to($this->adminEmail)
                ->context(['comment' => $comment])
                );
        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', [
                'comment' => $comment->getId(),
                'state' => $comment->getState()
            ]);
        }
    }
}