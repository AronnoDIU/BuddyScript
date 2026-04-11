<?php

declare(strict_types=1);

namespace CoreBundle\Entity;

use CoreBundle\Entity\Post\Like;
use CoreBundle\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Index(name: 'idx_post_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_post_visibility', columns: ['visibility'])]
class Post
{
    public const string VISIBILITY_PUBLIC = 'public';
    public const string VISIBILITY_PRIVATE = 'private';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(length: 20)]
    private string $visibility = self::VISIBILITY_PUBLIC;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $hashtags = [];

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $topics = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Comment> */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', orphanRemoval: true)]
    private Collection $comments;

    /** @var Collection<int, Like> */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'post', orphanRemoval: true)]
    private Collection $likes;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->hashtags = [];
        $this->topics = [];
        $this->comments = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = trim($content);

        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): self
    {
        $allowed = [self::VISIBILITY_PUBLIC, self::VISIBILITY_PRIVATE];
        $this->visibility = in_array($visibility, $allowed, true) ? $visibility : self::VISIBILITY_PUBLIC;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Comment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /** @return Collection<int, Like> */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /** @return list<string> */
    public function getHashtags(): array
    {
        return $this->hashtags ?? [];
    }

    /** @param list<string> $hashtags */
    public function setHashtags(array $hashtags): self
    {
        $normalized = array_map(
                static fn(string $value): string => mb_strtolower(trim($value)),
                $hashtags
            )
                |> array_filter(...)
                |> array_unique(...)
                |> array_values(...);
        $this->hashtags = $normalized;

        return $this;
    }

    /** @return list<string> */
    public function getTopics(): array
    {
        return $this->topics ?? [];
    }

    /** @param list<string> $topics */
    public function setTopics(array $topics): self
    {
        $normalized = array_map(
                static fn(string $value): string => mb_strtolower(trim($value)),
                $topics
            )
                |> array_filter(...)
                |> array_unique(...)
                |> array_values(...);
        $this->topics = $normalized;

        return $this;
    }
}
