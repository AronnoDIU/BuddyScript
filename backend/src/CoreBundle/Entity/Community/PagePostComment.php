<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\PagePostCommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PagePostCommentRepository::class)]
#[ORM\Index(name: 'idx_page_comment_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_page_comment_post', columns: ['post_id'])]
class PagePostComment
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: PagePost::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private PagePost $post;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $replies;

    /** @var Collection<int, PagePostCommentLike> */
    #[ORM\OneToMany(mappedBy: 'comment', targetEntity: PagePostCommentLike::class, cascade: ['persist', 'remove'])]
    private Collection $likes;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->replies = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPost(): PagePost
    {
        return $this->post;
    }

    public function setPost(PagePost $post): self
    {
        $this->post = $post;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(self $reply): self
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setParent($this);
        }
        return $this;
    }

    public function removeReply(self $reply): self
    {
        if ($this->replies->removeElement($reply)) {
            if ($reply->getParent() === $this) {
                $reply->setParent(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, PagePostCommentLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(PagePostCommentLike $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setComment($this);
        }
        return $this;
    }

    public function removeLike(PagePostCommentLike $like): self
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getComment() === $this) {
                $like->setComment(null);
            }
        }
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

    public function getLikeCount(): int
    {
        return $this->likes->count();
    }

    public function getReplyCount(): int
    {
        return $this->replies->count();
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

    public function isReply(): bool
    {
        return $this->parent !== null;
    }
}
