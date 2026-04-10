<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\GroupMembershipRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GroupMembershipRepository::class)]
#[ORM\Index(name: 'idx_group_membership_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_group_membership_group', columns: ['group_id'])]
#[ORM\Index(name: 'idx_group_membership_role', columns: ['role'])]
#[ORM\UniqueConstraint(name: 'uniq_group_user', columns: ['user_id', 'group_id'])]
class GroupMembership
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Group $group;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $role = Group::ROLE_MEMBER;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $invitedBy = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function setGroup(Group $group): self
    {
        $this->group = $group;
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
        if (!in_array($role, [Group::ROLE_ADMIN, Group::ROLE_MODERATOR, Group::ROLE_MEMBER], true)) {
            throw new \InvalidArgumentException('Invalid group role');
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

    public function isAdmin(): bool
    {
        return $this->role === Group::ROLE_ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->role === Group::ROLE_MODERATOR;
    }

    public function isMember(): bool
    {
        return $this->role === Group::ROLE_MEMBER;
    }

    public function canModerate(): bool
    {
        return in_array($this->role, [Group::ROLE_ADMIN, Group::ROLE_MODERATOR], true);
    }

    public function canAdmin(): bool
    {
        return $this->role === Group::ROLE_ADMIN;
    }
}
