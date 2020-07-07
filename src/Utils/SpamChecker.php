<?php

namespace App\Utils;

use App\Entity\Comment;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTimeImmutable;
use RuntimeException;

class SpamChecker
{
    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $client;
    private string $endpoint;

    /**
     * SpamChecker constructor.
     * @param HttpClientInterface $client
     * @param string $akismetKey
     */
    public function __construct(HttpClientInterface $client, string $akismetKey)
    {
        $this->client = $client;
        $this->endpoint = sprintf('https://%s.rest.akismet.com/1.1/comment-check', $akismetKey);
    }

    /**
     * @param Comment $comment
     * @param array $context
     * @return int
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getSpamScore(Comment $comment, array $context): int
    {
        /** @var DateTimeImmutable $commentDate */
        $commentDate = $comment->getCreatedAt();
        $response = $this->client->request('POST', $this->endpoint, [
           'body' => array_merge($context, [
               'blog' => 'https://guestbook.example.com',
               'comment_type' => 'comment',
               'comment_author' => $comment->getAuthor(),
               'comment_author_email' => $comment->getEmail(),
               'comment_content' => $comment->getText(),
               'comment_date_gmt' => $commentDate->format('c'),
               'blog_lang' => 'en',
               'blog_charset' => 'UTF-8',
               'is_test' => true
           ])
        ]);

        $headers = $response->getHeaders();

        if ('discard' === ($headers['x-akismet-pro-tip'][0] ?? '')) {
            return 2;
        }

        $content = $response->getContent();
        if (isset($headers['x-akismet-debug-help'][0])) {
            throw new RuntimeException(
                'Unable to check for spam: %s (%s)', $content, $headers['x-akismet-debug-help'][0]
            );
        }

        return 'true' === $content ? 1 : 0;
    }
}