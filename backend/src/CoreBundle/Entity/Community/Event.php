<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Index(name: 'idx_event_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_event_start_date', columns: ['start_date'])]
#[ORM\Index(name: 'idx_event_name', columns: ['name'])]
class Event
{
    public const string TYPE_ONLINE = 'online';
    public const string TYPE_OFFLINE = 'offline';
    public const string TYPE_HYBRID = 'hybrid';

    public const string ROLE_ORGANIZER = 'organizer';
    public const string ROLE_COORGANIZER = 'coorganizer';
    public const string ROLE_ATTENDEE = 'attendee';
    public const string ROLE_SPEAKER = 'speaker';

    public const string STATUS_UPCOMING = 'upcoming';
    public const string STATUS_ONGOING = 'ongoing';
    public const string STATUS_COMPLETED = 'completed';
    public const string STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarPath = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_OFFLINE;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_UPCOMING;

    #[ORM\Column]
    private \DateTimeImmutable $startDate;

    #[ORM\Column]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $onlineUrl = null;

    #[ORM\Column(type: 'integer')]
    private int $maxAttendees = 0;

    #[ORM\Column(type: 'json')]
    private array $settings = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $creator;

    /** @var Collection<int, EventMembership> */
    #[ORM\OneToMany(targetEntity: EventMembership::class, mappedBy: 'event', cascade: ['persist', 'remove'])]
    private Collection $memberships;

    /** @var Collection<int, EventPost> */
    #[ORM\OneToMany(targetEntity: EventPost::class, mappedBy: 'event', cascade: ['persist', 'remove'])]
    private Collection $posts;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->memberships = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->settings = [
            'allow_public_posts' => true,
            'require_approval' => false,
            'enable_discussion' => true,
            'send_reminders' => true,
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_ONLINE, self::TYPE_OFFLINE, self::TYPE_HYBRID], true)) {
            throw new \InvalidArgumentException('Invalid event type');
        }
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_UPCOMING, self::STATUS_ONGOING, self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            throw new \InvalidArgumentException('Invalid event status');
        }
        $this->status = $status;
        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location ? trim($location) : null;
        return $this;
    }

    public function getOnlineUrl(): ?string
    {
        return $this->onlineUrl;
    }

    public function setOnlineUrl(?string $onlineUrl): self
    {
        $this->onlineUrl = $onlineUrl ? trim($onlineUrl) : null;
        return $this;
    }

    public function getMaxAttendees(): int
    {
        return $this->maxAttendees;
    }

    public function setMaxAttendees(int $maxAttendees): self
    {
        $this->maxAttendees = max(0, $maxAttendees);
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
     * @return Collection<int, EventMembership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function addMembership(EventMembership $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setEvent($this);
        }
        return $this;
    }

    public function removeMembership(EventMembership $membership): self
    {
        if ($this->memberships->removeElement($membership)) {
            if ($membership->getEvent() === $this) {
                $membership->setEvent(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, EventPost>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(EventPost $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setEvent($this);
        }
        return $this;
    }

    public function removePost(EventPost $post): self
    {
        if ($this->posts->removeElement($post) && $post->getEvent() === $this) {
            $post->setEvent(null);
        }
        return $this;
    }

    public function getAttendeeCount(): int
    {
        $count = 0;
        foreach ($this->memberships as $membership) {
            if ($membership->getRole() === self::ROLE_ATTENDEE) {
                $count++;
            }
        }
        return $count;
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

        return match ($permission) {
            'view' => true,
            'discover' => true,
            'post' => in_array($role, [self::ROLE_ORGANIZER, self::ROLE_COORGANIZER, self::ROLE_SPEAKER], true) || ($this->settings['allow_public_posts'] ?? true),
            'moderate' => in_array($role, [self::ROLE_ORGANIZER, self::ROLE_COORGANIZER], true),
            'admin' => $role === self::ROLE_ORGANIZER,
            'attend' => $role === self::ROLE_ATTENDEE,
            default => false,
        };
    }

    public function isFull(): bool
    {
        return $this->maxAttendees > 0 && $this->getAttendeeCount() >= $this->maxAttendees;
    }

    public function isPast(): bool
    {
        return $this->endDate < new \DateTimeImmutable();
    }

    public function isOngoing(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->startDate <= $now && $this->endDate >= $now;
    }

    public function isUpcoming(): bool
    {
        return $this->startDate > new \DateTimeImmutable();
    }
}
