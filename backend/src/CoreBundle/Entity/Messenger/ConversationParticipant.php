<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Messenger;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Messenger\ConversationParticipantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ConversationParticipantRepository::class)]
#[ORM\Table(name: 'messenger_conversation_participant')]
#[ORM\UniqueConstraint(name: 'uniq_messenger_conversation_user', columns: ['conversation_id', 'user_id'])]
class ConversationParticipant
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messengerParticipations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastReadAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastDeliveredAt = null;

    #[ORM\Column]
    private bool $isPinned = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $mutedUntil = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation): self
    {
        $this->conversation = $conversation;

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

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function getLastReadAt(): ?\DateTimeImmutable
    {
        return $this->lastReadAt;
    }

    public function markRead(?\DateTimeImmutable $at = null): self
    {
        $this->lastReadAt = $at ?? new \DateTimeImmutable();

        return $this;
    }

    public function getLastDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->lastDeliveredAt;
    }

    public function markDelivered(?\DateTimeImmutable $at = null): self
    {
        $timestamp = $at ?? new \DateTimeImmutable();
        if ($this->lastDeliveredAt === null || $this->lastDeliveredAt < $timestamp) {
            $this->lastDeliveredAt = $timestamp;
        }

        return $this;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function setPinned(bool $pinned): self
    {
        $this->isPinned = $pinned;

        return $this;
    }

    public function getMutedUntil(): ?\DateTimeImmutable
    {
        return $this->mutedUntil;
    }

    public function setMutedUntil(?\DateTimeImmutable $mutedUntil): self
    {
        $this->mutedUntil = $mutedUntil;

        return $this;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): self
    {
        $this->archivedAt = $archivedAt;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archivedAt instanceof \DateTimeImmutable;
    }
}
