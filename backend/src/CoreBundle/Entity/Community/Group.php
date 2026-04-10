<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: 'community_group')]
#[ORM\Index(name: 'idx_group_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_group_visibility', columns: ['visibility'])]
#[ORM\Index(name: 'idx_group_name', columns: ['name'])]
class Group
{
    public const string VISIBILITY_PUBLIC = 'public';
    public const string VISIBILITY_PRIVATE = 'private';
    public const string VISIBILITY_SECRET = 'secret';

    public const string ROLE_ADMIN = 'admin';
    public const string ROLE_MODERATOR = 'moderator';
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

    #[ORM\Column(length: 20)]
    private string $visibility = self::VISIBILITY_PUBLIC;

    #[ORM\Column(type: 'json')]
    private array $settings = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $creator;

    /** @var Collection<int, GroupMembership> */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupMembership::class, cascade: ['persist', 'remove'])]
    private Collection $memberships;

    /** @var Collection<int, GroupPost> */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupPost::class, cascade: ['persist', 'remove'])]
    private Collection $posts;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->memberships = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->settings = [
            'allow_member_posts' => true,
            'require_approval' => false,
            'enable_discussion' => true,
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

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): self
    {
        if (!in_array($visibility, [self::VISIBILITY_PUBLIC, self::VISIBILITY_PRIVATE, self::VISIBILITY_SECRET], true)) {
            throw new \InvalidArgumentException('Invalid visibility type');
        }
        $this->visibility = $visibility;
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
     * @return Collection<int, GroupMembership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function addMembership(GroupMembership $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setGroup($this);
        }
        return $this;
    }

    public function removeMembership(GroupMembership $membership): self
    {
        $this->memberships->removeElement($membership);
        return $this;
    }

    /**
     * @return Collection<int, GroupPost>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(GroupPost $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setGroup($this);
        }
        return $this;
    }

    public function removePost(GroupPost $post): self
    {
        $this->posts->removeElement($post);
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
            'view' => $this->visibility !== self::VISIBILITY_SECRET || $role !== null,
            'post' => ($this->settings['allow_member_posts'] ?? true) && in_array($role, [self::ROLE_ADMIN, self::ROLE_MODERATOR, self::ROLE_MEMBER], true),
            'moderate' => in_array($role, [self::ROLE_ADMIN, self::ROLE_MODERATOR], true),
            'admin' => $role === self::ROLE_ADMIN,
            default => false,
        };
    }
}
