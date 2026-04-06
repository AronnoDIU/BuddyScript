<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Post\Like;
use App\Repository\PostRepository;
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
    public Uuid $id {
        get {
            return $this->id;
        }
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(length: 20)]
    private string $visibility = self::VISIBILITY_PUBLIC;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column]
    public \DateTimeImmutable $createdAt {
        get {
            return $this->createdAt;
        }
    }

    /** @var Collection<int, Comment> */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', orphanRemoval: true)]
    public Collection $comments {
        get {
            return $this->comments;
        }
    }

    /** @var Collection<int, Like> */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'post', orphanRemoval: true)]
    public Collection $likes {
        get {
            return $this->likes;
        }
    }

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getAuthor(): User
    {
        return $this->author;
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

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;

        return $this;
    }
}
