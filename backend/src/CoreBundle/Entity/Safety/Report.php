<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Safety;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Safety\ReportRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'safety_report')]
#[ORM\Index(name: 'idx_safety_report_target', columns: ['target_type', 'target_id'])]
#[ORM\Index(name: 'idx_safety_report_created_at', columns: ['created_at'])]
class Report
{
    public const string STATUS_OPEN = 'open';
    public const string STATUS_REVIEWED = 'reviewed';
    public const string STATUS_ACTIONED = 'actioned';
    public const string STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $reporter;

    #[ORM\Column(length: 40)]
    private string $targetType;

    #[ORM\Column(length: 64)]
    private string $targetId;

    #[ORM\Column(length: 40)]
    private string $category;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(length: 24)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resolutionNote = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getReporter(): User
    {
        return $this->reporter;
    }

    public function setReporter(User $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function setTargetType(string $targetType): self
    {
        $this->targetType = mb_strtolower(trim($targetType));

        return $this;
    }

    public function getTargetId(): string
    {
        return $this->targetId;
    }

    public function setTargetId(string $targetId): self
    {
        $this->targetId = trim($targetId);

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = mb_strtolower(trim($category));

        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = trim($reason);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_OPEN, self::STATUS_REVIEWED, self::STATUS_ACTIONED, self::STATUS_REJECTED], true)) {
            throw new \InvalidArgumentException('Invalid report status.');
        }

        $this->status = $status;

        return $this;
    }

    public function getResolutionNote(): ?string
    {
        return $this->resolutionNote;
    }

    public function setResolutionNote(?string $resolutionNote): self
    {
        $this->resolutionNote = $resolutionNote !== null ? trim($resolutionNote) : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }
}

