<?php

declare(strict_types=1);

namespace CoreBundle\Entity\Messenger;

use CoreBundle\Entity\User;
use CoreBundle\Repository\Messenger\MessageReceiptRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MessageReceiptRepository::class)]
#[ORM\Table(name: 'messenger_message_receipt')]
#[ORM\UniqueConstraint(name: 'uniq_messenger_receipt_message_user', columns: ['message_id', 'recipient_id'])]
class MessageReceipt
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'receipts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Message $message;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function setRecipient(User $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function markDelivered(?\DateTimeImmutable $at = null): self
    {
        $timestamp = $at ?? new \DateTimeImmutable();
        if ($this->deliveredAt === null || $this->deliveredAt < $timestamp) {
            $this->deliveredAt = $timestamp;
        }

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function markRead(?\DateTimeImmutable $at = null): self
    {
        $timestamp = $at ?? new \DateTimeImmutable();
        $this->markDelivered($timestamp);
        if ($this->readAt === null || $this->readAt < $timestamp) {
            $this->readAt = $timestamp;
        }

        return $this;
    }
}

