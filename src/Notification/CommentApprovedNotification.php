<?php

namespace App\Notification;

use App\Entity\Comment;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackDividerBlock;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackSectionBlock;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;

class CommentApprovedNotification extends Notification implements EmailNotificationInterface, ChatNotificationInterface
{
    /** @var Comment */
    private Comment $comment;

    /**
     * CommentReviewNotification constructor.
     * @param Comment $comment
     */
    public function __construct(Comment $comment)
    {
        parent::__construct('Your comment was approved and posted!');
        $this->comment = $comment;
    }

    public function asEmailMessage(Recipient $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient, $transport);
        $message->getMessage()
            ->htmlTemplate('email/comment_approved.html.twig')
            ->context(['comment' => $this->comment]);

        return $message;
    }

    public function asChatMessage(Recipient $recipient, string $transport=null): ?ChatMessage
    {
        if('slack' !== $transport) {
            return null;
        }

        $message = ChatMessage::fromNotification($this, $recipient, $transport = null);
        $message->subject($this->getSubject());
        $message->options((new SlackOptions())
            ->iconEmoji('tada')
            ->iconUrl('https://guestbook.example.com')
            ->username('Guestbook')
            ->block((new SlackSectionBlock())->text($this->getSubject()))
            ->block(new SlackDividerBlock())
            ->block((new SlackSectionBlock())->text(
                sprintf(
                    'Congratulations, the following comment was approved and posted: %s (%s) says: %s',
                    $this->comment->getAuthor(),
                    $this->comment->getEmail(),
                    $this->comment->getText()
                ))
            )
        );

        return $message;
    }

    public function getChannels(Recipient $recipient): array
    {
        if(preg_match('{\b(great|awesome)\b}i', $this->comment->getText())) {
            return ['email', 'chat/slack'];
        }

        $this->importance((Notification::IMPORTANCE_LOW));

        return ['email'];
    }
}