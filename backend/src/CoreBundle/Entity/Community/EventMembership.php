<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\EventMembershipRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EventMembershipRepository::class)]
#[ORM\Index(name: 'idx_event_membership_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_event_membership_event', columns: ['event_id'])]
#[ORM\Index(name: 'idx_event_membership_role', columns: ['role'])]
#[ORM\UniqueConstraint(name: 'uniq_event_user', columns: ['user_id', 'event_id'])]
class EventMembership
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Event $event;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $role = Event::ROLE_ATTENDEE;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $invitedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): self
    {
        $this->event = $event;
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

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        if (!in_array($role, [Event::ROLE_ORGANIZER, Event::ROLE_COORGANIZER, Event::ROLE_ATTENDEE, Event::ROLE_SPEAKER], true)) {
            throw new \InvalidArgumentException('Invalid event role');
        }
        $this->role = $role;
        return $this;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): self
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getInvitedBy(): ?\DateTimeImmutable
    {
        return $this->invitedBy;
    }

    public function setInvitedBy(?\DateTimeImmutable $invitedBy): self
    {
        $this->invitedBy = $invitedBy;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes ? trim($notes) : null;
        return $this;
    }

    public function isOrganizer(): bool
    {
        return $this->role === Event::ROLE_ORGANIZER;
    }

    public function isCoorganizer(): bool
    {
        return $this->role === Event::ROLE_COORGANIZER;
    }

    public function isAttendee(): bool
    {
        return $this->role === Event::ROLE_ATTENDEE;
    }

    public function isSpeaker(): bool
    {
        return $this->role === Event::ROLE_SPEAKER;
    }

    public function canModerate(): bool
    {
        return in_array($this->role, [Event::ROLE_ORGANIZER, Event::ROLE_COORGANIZER], true);
    }

    public function canAdmin(): bool
    {
        return $this->role === Event::ROLE_ORGANIZER;
    }

    public function canPost(): bool
    {
        return in_array($this->role, [Event::ROLE_ORGANIZER, Event::ROLE_COORGANIZER, Event::ROLE_SPEAKER], true);
    }
}
