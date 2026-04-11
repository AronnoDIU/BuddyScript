<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\PageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Index(name: 'idx_page_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_page_category', columns: ['category'])]
#[ORM\Index(name: 'idx_page_name', columns: ['name'])]
class Page
{
    public const string CATEGORY_BUSINESS = 'business';
    public const string CATEGORY_ORGANIZATION = 'organization';
    public const string CATEGORY_COMMUNITY = 'community';
    public const string CATEGORY_ENTERTAINMENT = 'entertainment';
    public const string CATEGORY_BRAND = 'brand';
    public const string CATEGORY_CAUSE = 'cause';
    public const string CATEGORY_OTHER = 'other';

    public const string ROLE_ADMIN = 'admin';
    public const string ROLE_EDITOR = 'editor';
    public const string ROLE_MEMBER = 'member';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverPath = null;

    #[ORM\Column(length: 30)]
    private string $category = self::CATEGORY_OTHER;

    #[ORM\Column(type: 'json')]
    private array $settings = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $creator;

    /** @var Collection<int, PageMembership> */
    #[ORM\OneToMany(targetEntity: PageMembership::class, mappedBy: 'page', cascade: ['persist', 'remove'])]
    private Collection $memberships;

    /** @var Collection<int, PagePost> */
    #[ORM\OneToMany(targetEntity: PagePost::class, mappedBy: 'page', cascade: ['persist', 'remove'])]
    private Collection $posts;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->memberships = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->settings = [
            'allow_public_posts' => false,
            'require_approval' => true,
            'enable_comments' => true,
            'show_member_list' => true,
        ];
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description ? trim($description) : null;
        return $this;
    }

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): self
    {
        $this->avatarPath = $avatarPath;
        return $this;
    }

    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }

    public function setCoverPath(?string $coverPath): self
    {
        $this->coverPath = $coverPath;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $validCategories = [
            self::CATEGORY_BUSINESS,
            self::CATEGORY_ORGANIZATION,
            self::CATEGORY_COMMUNITY,
            self::CATEGORY_ENTERTAINMENT,
            self::CATEGORY_BRAND,
            self::CATEGORY_CAUSE,
            self::CATEGORY_OTHER,
        ];

        if (!in_array($category, $validCategories, true)) {
            throw new \InvalidArgumentException('Invalid page category');
        }

        $this->category = $category;
        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): self
    {
        $this->settings = $settings;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function setCreator(User $creator): self
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return Collection<int, PageMembership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function addMembership(PageMembership $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setPage($this);
        }
        return $this;
    }

    public function removeMembership(PageMembership $membership): self
    {
        if ($this->memberships->removeElement($membership) && $membership->getPage() === $this) {
            $membership->setPage(null);
        }
        return $this;
    }

    /**
     * @return Collection<int, PagePost>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(PagePost $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setPage($this);
        }
        return $this;
    }

    public function removePost(PagePost $post): self
    {
        if ($this->posts->removeElement($post) && $post->getPage() === $this) {
            $post->setPage(null);
        }
        return $this;
    }

    public function getMemberCount(): int
    {
        return $this->memberships->count();
    }

    public function getPostCount(): int
    {
        return $this->posts->count();
    }

    public function isMember(User $user): bool
    {
        foreach ($this->memberships as $membership) {
            if ($membership->getUser()->getId()->equals($user->getId())) {
                return true;
            }
        }
        return false;
    }

    public function getMembershipRole(User $user): ?string
    {
        foreach ($this->memberships as $membership) {
            if ($membership->getUser()->getId()->equals($user->getId())) {
                return $membership->getRole();
            }
        }
        return null;
    }

    public function hasPermission(User $user, string $permission): bool
    {
        $role = $this->getMembershipRole($user);

        if ($role === null) {
            return false;
        }

        return match ($permission) {
            'view' => true, // Pages are generally public
            'post' => in_array($role, [self::ROLE_ADMIN, self::ROLE_EDITOR], true) || ($this->settings['allow_public_posts'] ?? false),
            'edit' => in_array($role, [self::ROLE_ADMIN, self::ROLE_EDITOR], true),
            'admin' => $role === self::ROLE_ADMIN,
            default => false,
        };
    }
}
