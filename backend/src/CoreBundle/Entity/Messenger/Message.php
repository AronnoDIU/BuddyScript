<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Messenger;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Messenger\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'messenger_message')]
#[ORM\Index(name: 'idx_messenger_message_created', columns: ['created_at'])]
class Message
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sentMessages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $sender;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, MessageAttachment> */
    #[ORM\OneToMany(targetEntity: MessageAttachment::class, mappedBy: 'message', orphanRemoval: true)]
    private Collection $attachments;

    /** @var Collection<int, MessageReceipt> */
    #[ORM\OneToMany(targetEntity: MessageReceipt::class, mappedBy: 'message', orphanRemoval: true)]
    private Collection $receipts;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->attachments = new ArrayCollection();
        $this->receipts = new ArrayCollection();
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

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content !== null ? trim($content) : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, MessageAttachment> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    /** @return Collection<int, MessageReceipt> */
    public function getReceipts(): Collection
    {
        return $this->receipts;
    }
}
