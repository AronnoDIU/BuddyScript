<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Marketplace;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Marketplace\ListingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\Table(name: 'marketplace_listing')]
#[ORM\Index(name: 'idx_marketplace_listing_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_marketplace_listing_status', columns: ['status'])]
#[ORM\Index(name: 'idx_marketplace_listing_category', columns: ['category'])]
class Listing
{
    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_SOLD = 'sold';
    public const string STATUS_DRAFT = 'draft';
    public const string STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $seller;

    #[ORM\Column(length: 140)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column]
    private int $priceAmount = 0;

    #[ORM\Column(length: 8)]
    private string $currency = 'USD';

    #[ORM\Column(length: 64)]
    private string $category = 'general';

    #[ORM\Column(length: 32)]
    private string $conditionType = 'used';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 24)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'json')]
    private array $tags = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSeller(): User
    {
        return $this->seller;
    }

    public function setSeller(User $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPriceAmount(): int
    {
        return $this->priceAmount;
    }

    public function setPriceAmount(int $priceAmount): self
    {
        $this->priceAmount = max(0, $priceAmount);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = strtoupper(trim($currency));
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = mb_strtolower(trim($category));
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getConditionType(): string
    {
        return $this->conditionType;
    }

    public function setConditionType(string $conditionType): self
    {
        $this->conditionType = mb_strtolower(trim($conditionType));
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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location !== null ? trim($location) : null;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_SOLD, self::STATUS_DRAFT, self::STATUS_ARCHIVED], true)) {
            throw new \InvalidArgumentException('Invalid listing status.');
        }

        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $normalized = [];
        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                continue;
            }
            $clean = mb_strtolower(trim($tag));
            if ($clean === '') {
                continue;
            }
            $normalized[] = $clean;
        }

        $this->tags = array_values(array_unique($normalized));
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
}

