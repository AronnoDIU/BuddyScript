<?php

declare(strict_types=1);

namespace CoreBundle\Entity;

use CoreBundle\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'idx_notification_recipient_read', columns: ['recipient_id', 'is_read'])]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $resourceType = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $resourceId = null;

    /** @var array<string,mixed> */
    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column(options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function setRecipient(User $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): self
    {
        $this->actor = $actor;

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

    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    public function setResourceType(?string $resourceType): self
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    public function getResourceId(): ?Uuid
    {
        return $this->resourceId;
    }

    public function setResourceId(?Uuid $resourceId): self
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    /** @return array<string,mixed> */
    public function getData(): array
    {
        return $this->data;
    }

    /** @param array<string,mixed> $data */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function markAsRead(): self
    {
        if ($this->isRead) {
            return $this;
        }

        $this->isRead = true;
        $this->readAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }
}

