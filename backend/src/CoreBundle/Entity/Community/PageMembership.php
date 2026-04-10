<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Community;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\PageMembershipRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PageMembershipRepository::class)]
#[ORM\Index(name: 'idx_page_membership_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_page_membership_page', columns: ['page_id'])]
#[ORM\Index(name: 'idx_page_membership_role', columns: ['role'])]
#[ORM\UniqueConstraint(name: 'uniq_page_user', columns: ['user_id', 'page_id'])]
class PageMembership
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Page $page;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $role = Page::ROLE_MEMBER;

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

    public function getPage(): Page
    {
        return $this->page;
    }

    public function setPage(Page $page): self
    {
        $this->page = $page;
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
        if (!in_array($role, [Page::ROLE_ADMIN, Page::ROLE_EDITOR, Page::ROLE_MEMBER], true)) {
            throw new \InvalidArgumentException('Invalid page role');
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
        return $this->role === Page::ROLE_ADMIN;
    }

    public function isEditor(): bool
    {
        return $this->role === Page::ROLE_EDITOR;
    }

    public function isMember(): bool
    {
        return $this->role === Page::ROLE_MEMBER;
    }

    public function canEdit(): bool
    {
        return in_array($this->role, [Page::ROLE_ADMIN, Page::ROLE_EDITOR], true);
    }

    public function canAdmin(): bool
    {
        return $this->role === Page::ROLE_ADMIN;
    }
}
