<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Safety;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Safety\UserBlockRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserBlockRepository::class)]
#[ORM\Table(name: 'safety_user_block')]
#[ORM\UniqueConstraint(name: 'uniq_safety_user_block', columns: ['blocker_id', 'blocked_id'])]
class UserBlock
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $blocker;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $blocked;

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

    public function getBlocker(): User
    {
        return $this->blocker;
    }

    public function setBlocker(User $blocker): self
    {
        $this->blocker = $blocker;

        return $this;
    }

    public function getBlocked(): User
    {
        return $this->blocked;
    }

    public function setBlocked(User $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

