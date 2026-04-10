<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\GroupPostLikeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GroupPostLikeRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_group_post_like', columns: ['post_id', 'user_id'])]
class GroupPostLike
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: GroupPost::class, inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GroupPost $post;

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

    public function getPost(): GroupPost
    {
        return $this->post;
    }

    public function setPost(GroupPost $post): self
    {
        $this->post = $post;
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
