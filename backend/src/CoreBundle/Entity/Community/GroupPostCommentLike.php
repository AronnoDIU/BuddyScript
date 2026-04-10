<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\GroupPostCommentLikeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GroupPostCommentLikeRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_group_comment_like', columns: ['comment_id', 'user_id'])]
class GroupPostCommentLike
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: GroupPostComment::class, inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GroupPostComment $comment;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getComment(): GroupPostComment
    {
        return $this->comment;
    }

    public function setComment(GroupPostComment $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
