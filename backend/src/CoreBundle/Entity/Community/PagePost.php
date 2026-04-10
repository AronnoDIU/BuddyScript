<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\PagePostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PagePostRepository::class)]
#[ORM\Index(name: 'idx_page_post_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_page_post_page', columns: ['page_id'])]
class PagePost
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Page $page;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(type: 'json')]
    private array $hashtags = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, PagePostLike> */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: PagePostLike::class, cascade: ['persist', 'remove'])]
    private Collection $likes;

    /** @var Collection<int, PagePostComment> */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: PagePostComment::class, cascade: ['persist', 'remove'])]
    private Collection $comments;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->likes = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function setPage(Page $page): self
    {
        $this->page = $page;
        return $this;
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
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getHashtags(): array
    {
        return $this->hashtags;
    }

    public function setHashtags(array $hashtags): self
    {
        $this->hashtags = $hashtags;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, PagePostLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(PagePostLike $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setPost($this);
        }
        return $this;
    }

    public function removeLike(PagePostLike $like): self
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getPost() === $this) {
                $like->setPost(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, PagePostComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(PagePostComment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
        }
        return $this;
    }

    public function removeComment(PagePostComment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }
        return $this;
    }

    public function getLikeCount(): int
    {
        return $this->likes->count();
    }

    public function getCommentCount(): int
    {
        return $this->comments->count();
    }

    public function isLikedBy(User $user): bool
    {
        foreach ($this->likes as $like) {
            if ($like->getUser()->getId()->equals($user->getId())) {
                return true;
            }
        }
        return false;
    }
}
