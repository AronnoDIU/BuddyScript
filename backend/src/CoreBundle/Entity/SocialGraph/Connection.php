<?php

declare(strict_types=1);

namespace CoreBundle\Entity\SocialGraph;

use CoreBundle\Entity\User;
use CoreBundle\Repository\SocialGraph\ConnectionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ConnectionRepository::class)]
#[ORM\Table(name: 'social_connection')]
#[ORM\UniqueConstraint(name: 'uniq_social_connection_pair', columns: ['requester_id', 'addressee_id'])]
#[ORM\Index(name: 'idx_social_connection_status', columns: ['status'])]
class Connection
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'connectionsSent')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $requester;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'connectionsReceived')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $addressee;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $listKey = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRequester(): User
    {
        return $this->requester;
    }

    public function setRequester(User $requester): self
    {
        $this->requester = $requester;

        return $this;
    }

    public function getAddressee(): User
    {
        return $this->addressee;
    }

    public function setAddressee(User $addressee): self
    {
        $this->addressee = $addressee;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $allowed = [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_REJECTED];
        $this->status = in_array($status, $allowed, true) ? $status : self::STATUS_PENDING;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getListKey(): ?string
    {
        return $this->listKey;
    }

    public function setListKey(?string $listKey): self
    {
        $this->listKey = $listKey !== null ? trim($listKey) : null;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

