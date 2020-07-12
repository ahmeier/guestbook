<?php

namespace App\Message\Comment;

class CommentMessage
{
    private array $context;
    private int $id;
    private string $reviewUrl;

    /**
     * CommentMessage constructor.
     * @param int $id
     * @param string $reviewUrl
     * @param array $context
     */
    public function __construct(int $id, string $reviewUrl, array $context = [])
    {
        $this->id = $id;
        $this->context = $context;
        $this->reviewUrl = $reviewUrl;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getReviewUrl(): string
    {
        return $this->reviewUrl;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}