<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=CommentRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @ApiResource(
 *     collectionOperations={"get"={"normalization_context"={"groups"="comment:list"}}},
 *     itemOperations={"get"={"normalization_context"={"groups"="comment:item"}}},
 *     order={"createdAt"="DESC"},
 *     paginationEnabled=false
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"conference": "exact"})
 */
class Comment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"comment:list", "comment:item"})
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"comment:list", "comment:item"})
     * @Assert\NotBlank
     */
    private string $author = '';

    /**
     * @ORM\Column(type="text")
     * @Groups({"comment:list", "comment:item"})
     * @Assert\NotBlank
     */
    private string $text = '';

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"comment:list", "comment:item"})
     * @Assert\NotBlank
     * @Assert\Email
     */
    private string $email = '';

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"comment:list", "comment:item"})
     */
    private ?DateTimeImmutable $createdAt = null;

    /**
     * @ORM\ManyToOne(targetEntity=Conference::class, inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"comment:list", "comment:item"})
     */
    private ?Conference $conference = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"comment:list", "comment:item"})
     */
    private ?string $photoFilename = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default": "submitted"})
     */
    private string $state = 'submitted';

    public function __toString()
    {
        return $this->email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getConference(): ?Conference
    {
        return $this->conference;
    }

    public function setConference(Conference $conference): self
    {
        $this->conference = $conference;

        return $this;
    }

    public function getPhotoFilename(): ?string
    {
        return $this->photoFilename;
    }

    public function setPhotoFilename(string $photoFilename): self
    {
        $this->photoFilename = $photoFilename;

        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }
}
