<?php

declare(strict_types=1);

namespace CoreBundle\Entity;

use CoreBundle\Repository\ReactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReactionRepository::class)]
#[ORM\Table(name: 'reaction')]
#[ORM\UniqueConstraint(name: 'uniq_reaction_actor_target', columns: ['user_id', 'target_type', 'target_id'])]
#[ORM\Index(name: 'idx_reaction_target', columns: ['target_type', 'target_id'])]
class Reaction
{
    public const TYPE_LIKE = 'like';
    public const TYPE_LOVE = 'love';
    public const TYPE_HAHA = 'haha';
    public const TYPE_WOW = 'wow';
    public const TYPE_SAD = 'sad';
    public const TYPE_ANGRY = 'angry';
    public const TYPE_CARE = 'care';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $targetType;

    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $targetId;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_LIKE;

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function setTargetType(string $targetType): self
    {
        $this->targetType = trim($targetType);

        return $this;
    }

    public function getTargetId(): Uuid
    {
        return $this->targetId;
    }

    public function setTargetId(Uuid $targetId): self
    {
        $this->targetId = $targetId;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = trim($type);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

