<?php

declare(strict_types=1);

namespace CoreBundle\Service;

use CoreBundle\Entity\Messenger\Conversation;
use CoreBundle\Entity\Messenger\ConversationParticipant;
use CoreBundle\Entity\Messenger\Message;
use CoreBundle\Entity\Messenger\MessageAttachment;
use CoreBundle\Entity\Messenger\MessageReceipt;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Messenger\ConversationParticipantRepository;
use CoreBundle\Repository\Messenger\ConversationRepository;
use CoreBundle\Repository\Messenger\MessageReceiptRepository;
use CoreBundle\Repository\Messenger\MessageRepository;
use CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class Messenger
{
    private readonly EntityManagerInterface $entityManager;

    private readonly ApiFormatter $formatter;

    private readonly string $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        ApiFormatter $formatter,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
        $this->projectDir = $projectDir;
    }

    /** @return array<string,mixed> */
    public function conversations(User $viewer, string $query = '', int $offset = 0, int $limit = 40, bool $includeArchived = false): array
    {
        $safeLimit = max(1, min(100, $limit));
        $safeOffset = max(0, $offset);

        $conversations = $this->getConversationRepository()->findForUser($viewer, $query, $safeLimit + 1, $safeOffset, $includeArchived);
        $hasMore = count($conversations) > $safeLimit;
        if ($hasMore) {
            array_pop($conversations);
        }

        return [
            'conversations' => array_map(fn (Conversation $conversation): array => $this->formatConversation($conversation, $viewer), $conversations),
            'pagination' => [
                'offset' => $safeOffset,
                'limit' => $safeLimit,
                'hasMore' => $hasMore,
                'nextOffset' => $safeOffset + count($conversations),
                'query' => trim($query),
                'includeArchived' => $includeArchived,
            ],
        ];
    }

    /** @return array<string,mixed>|null */
    public function messages(User $viewer, string $conversationId, ?string $beforeIso = null, int $limit = 80): ?array
    {
        $conversation = $this->getConversationRepository()->findForUserById($viewer, $conversationId);
        if (!$conversation instanceof Conversation) {
            return null;
        }

        $before = null;
        if ($beforeIso !== null && trim($beforeIso) !== '') {
            try {
                $before = new \DateTimeImmutable($beforeIso);
            } catch (\Exception) {
                throw new \InvalidArgumentException('Invalid `before` value.');
            }
        }

        $safeLimit = max(1, min(200, $limit));

        $messages = $this->getMessageRepository()->findForConversation($conversation, $safeLimit, $before);
        if ($this->markDelivered($conversation, $viewer)) {
            $this->entityManager->flush();
        }
        $oldest = $messages[0] ?? null;
        $hasMore = $oldest instanceof Message
            ? $this->getMessageRepository()->countOlderThan($conversation, $oldest->getCreatedAt()) > 0
            : false;


        return [
            'conversation' => $this->formatConversation($conversation, $viewer),
            'messages' => array_map(fn (Message $message): array => $this->formatMessage($message, $viewer), $messages),
            'pagination' => [
                'limit' => $safeLimit,
                'before' => $before?->format(DATE_ATOM),
                'hasMore' => $hasMore,
                'nextBefore' => $oldest instanceof Message ? $oldest->getCreatedAt()->format(DATE_ATOM) : null,
            ],
        ];
    }

    /** @return array<string,mixed> */
    public function sendMessage(
        User $sender,
        ?string $conversationId,
        ?string $recipientId,
        ?string $content,
        ?UploadedFile $attachment,
    ): array {
        $conversation = $this->resolveConversation($sender, $conversationId, $recipientId);

        $trimmedContent = trim((string) $content);
        if ($trimmedContent === '' && !$attachment instanceof UploadedFile) {
            throw new \InvalidArgumentException('Message content or attachment is required.');
        }

        $message = new Message();
        $message
            ->setConversation($conversation)
            ->setSender($sender)
            ->setContent($trimmedContent !== '' ? $trimmedContent : null);

        $this->entityManager->persist($message);

        if ($attachment instanceof UploadedFile) {
            $meta = $this->storeAttachment($attachment);
            $messageAttachment = new MessageAttachment();
            $messageAttachment
                ->setMessage($message)
                ->setPath($meta['path'])
                ->setOriginalName($meta['originalName'])
                ->setMimeType($meta['mimeType'])
                ->setSize($meta['size']);
            $this->entityManager->persist($messageAttachment);
        }

        $now = new \DateTimeImmutable();
        $conversation->markUpdated($now);

        $this->initializeReceiptsForMessage($message, $conversation, $sender);

        $senderParticipation = $this->getParticipantRepository()->findOneByConversationAndUser($conversation, $sender);
        if ($senderParticipation instanceof ConversationParticipant) {
            $senderParticipation->markRead($now);
            $senderParticipation->markDelivered($now);
        }

        $this->entityManager->flush();

        return [
            'conversation' => $this->formatConversation($conversation, $sender),
            'message' => $this->formatMessage($message, $sender),
        ];
    }

    /** @return array<string,mixed>|null */
    public function markRead(User $viewer, string $conversationId): ?array
    {
        $conversation = $this->getConversationRepository()->findForUserById($viewer, $conversationId);
        if (!$conversation instanceof Conversation) {
            return null;
        }

        $participant = $this->getParticipantRepository()->findOneByConversationAndUser($conversation, $viewer);
        if ($participant instanceof ConversationParticipant) {
            $now = new \DateTimeImmutable();
            $participant->markRead($now);
            $participant->markDelivered($now);

            $unreadReceipts = $this->getReceiptRepository()->findUnreadForConversationAndUser($conversation, $viewer);
            foreach ($unreadReceipts as $receipt) {
                $receipt->markRead($now);
            }

            $this->entityManager->flush();
        }

        return [
            'conversation' => $this->formatConversation($conversation, $viewer),
        ];
    }

    /** @return array<string,mixed> */
    public function updates(User $viewer, ?string $sinceIso): array
    {
        $since = null;
        if ($sinceIso !== null && trim($sinceIso) !== '') {
            try {
                $since = new \DateTimeImmutable($sinceIso);
            } catch (\Exception) {
                throw new \InvalidArgumentException('Invalid `since` value.');
            }
        }

        $messages = $this->getMessageRepository()->findRecentForUser($viewer, $since, 80);

        $latestByConversation = [];
        foreach ($messages as $message) {
            $conversationId = $message->getConversation()->getId()->toRfc4122();
            if (!isset($latestByConversation[$conversationId]) || $latestByConversation[$conversationId] < $message->getCreatedAt()) {
                $latestByConversation[$conversationId] = $message->getCreatedAt();
            }
        }

        $hasDeliveryUpdates = false;
        foreach ($latestByConversation as $conversationId => $timestamp) {
            $conversation = $this->getConversationRepository()->find($conversationId);
            if ($conversation instanceof Conversation && $timestamp instanceof \DateTimeImmutable) {
                $hasDeliveryUpdates = $this->markDelivered($conversation, $viewer, $timestamp) || $hasDeliveryUpdates;
            }
        }

        if ($hasDeliveryUpdates) {
            $this->entityManager->flush();
        }

        return [
            'updates' => array_map(fn (Message $message): array => $this->formatMessage($message, $viewer), $messages),
            'serverTime' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];
    }

    /** @return array<string,mixed>|null */
    public function pinConversation(User $viewer, string $conversationId, bool $pinned): ?array
    {
        $conversation = $this->getConversationRepository()->findForUserById($viewer, $conversationId);
        if (!$conversation instanceof Conversation) {
            return null;
        }

        $participant = $this->getParticipantRepository()->findOneByConversationAndUser($conversation, $viewer);
        if ($participant instanceof ConversationParticipant) {
            $participant->setPinned($pinned);
            $this->entityManager->flush();
        }

        return ['conversation' => $this->formatConversation($conversation, $viewer)];
    }

    /** @return array<string,mixed>|null */
    public function muteConversation(User $viewer, string $conversationId, int $minutes): ?array
    {
        $conversation = $this->getConversationRepository()->findForUserById($viewer, $conversationId);
        if (!$conversation instanceof Conversation) {
            return null;
        }

        $participant = $this->getParticipantRepository()->findOneByConversationAndUser($conversation, $viewer);
        if ($participant instanceof ConversationParticipant) {
            $participant->setMutedUntil($minutes > 0 ? (new \DateTimeImmutable())->modify(sprintf('+%d minutes', $minutes)) : null);
            $this->entityManager->flush();
        }

        return ['conversation' => $this->formatConversation($conversation, $viewer)];
    }

    /** @return array<string,mixed>|null */
    public function archiveConversation(User $viewer, string $conversationId, bool $archived): ?array
    {
        $conversation = $this->getConversationRepository()->findForUserById($viewer, $conversationId);
        if (!$conversation instanceof Conversation) {
            return null;
        }

        $participant = $this->getParticipantRepository()->findOneByConversationAndUser($conversation, $viewer);
        if ($participant instanceof ConversationParticipant) {
            $participant->setArchivedAt($archived ? new \DateTimeImmutable() : null);
            $this->entityManager->flush();
        }

        return ['conversation' => $this->formatConversation($conversation, $viewer)];
    }

    private function resolveConversation(User $sender, ?string $conversationId, ?string $recipientId): Conversation
    {
        if ($conversationId !== null && trim($conversationId) !== '') {
            $conversation = $this->getConversationRepository()->findForUserById($sender, $conversationId);
            if (!$conversation instanceof Conversation) {
                throw new \InvalidArgumentException('Conversation not found.');
            }

            return $conversation;
        }

        if ($recipientId === null || trim($recipientId) === '') {
            throw new \InvalidArgumentException('Recipient is required when conversation id is missing.');
        }

        $recipient = $this->getUserRepository()->findOneById($recipientId);
        if (!$recipient instanceof User) {
            throw new \InvalidArgumentException('Recipient not found.');
        }

        if ($recipient->getId()->equals($sender->getId())) {
            throw new \InvalidArgumentException('Cannot start a conversation with yourself.');
        }

        $existing = $this->getConversationRepository()->findDirectOneToOne($sender, $recipient);
        if ($existing instanceof Conversation) {
            return $existing;
        }

        $conversation = new Conversation();

        $senderParticipant = new ConversationParticipant();
        $senderParticipant->setConversation($conversation)->setUser($sender)->markRead();

        $recipientParticipant = new ConversationParticipant();
        $recipientParticipant->setConversation($conversation)->setUser($recipient);

        $this->entityManager->persist($conversation);
        $this->entityManager->persist($senderParticipant);
        $this->entityManager->persist($recipientParticipant);

        return $conversation;
    }

    /** @return array{path:string,originalName:string,mimeType:string,size:int} */
    private function storeAttachment(UploadedFile $file): array
    {
        $mimeType = (string) ($file->getMimeType() ?? 'application/octet-stream');
        $allowed = [
            'image/png',
            'image/jpeg',
            'image/webp',
            'image/gif',
            'application/pdf',
            'text/plain',
        ];

        if (!in_array($mimeType, $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported attachment format.');
        }

        $size = (int) ($file->getSize() ?? 0);
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('Attachment size must be between 1 byte and 10MB.');
        }

        $uploadDir = $this->projectDir . '/public/uploads/messages';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadDir));
        }

        $ext = pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION);
        $safeExt = $ext !== '' ? strtolower($ext) : 'bin';

        $name = Uuid::v7()->toRfc4122() . '.' . $safeExt;
        $file->move($uploadDir, $name);

        return [
            'path' => '/uploads/messages/' . $name,
            'originalName' => (string) $file->getClientOriginalName(),
            'mimeType' => $mimeType,
            'size' => $size,
        ];
    }

    /** @return array<string,mixed> */
    private function formatConversation(Conversation $conversation, User $viewer): array
    {
        $participants = $conversation->getParticipants();
        $me = null;
        $counterparts = [];

        foreach ($participants as $participant) {
            if (!$participant instanceof ConversationParticipant) {
                continue;
            }

            if ($participant->getUser()->getId()->equals($viewer->getId())) {
                $me = $participant;
                continue;
            }

            $counterparts[] = $this->formatter->user($participant->getUser());
        }

        $latestMessage = $this->getMessageRepository()->findLatestForConversation($conversation);
        $unreadCount = $this->getMessageRepository()->countUnreadForUser(
            $conversation,
            $viewer,
            $me instanceof ConversationParticipant ? $me->getLastReadAt() : null,
        );

        return [
            'id' => $conversation->getId()->toRfc4122(),
            'participants' => $counterparts,
            'lastMessageAt' => $conversation->getLastMessageAt()?->format(DATE_ATOM),
            'unreadCount' => $unreadCount,
            'lastReadAt' => $me?->getLastReadAt()?->format(DATE_ATOM),
            'latestMessage' => $latestMessage instanceof Message ? $this->formatMessage($latestMessage, $viewer) : null,
            'isPinned' => $me?->isPinned() ?? false,
            'mutedUntil' => $me?->getMutedUntil()?->format(DATE_ATOM),
            'isArchived' => $me?->isArchived() ?? false,
        ];
    }

    /** @return array<string,mixed> */
    private function formatMessage(Message $message, User $viewer): array
    {
        $conversation = $message->getConversation();
        $receipts = $this->getReceiptRepository()->findByMessage($message);
        $readBy = [];
        $deliveredBy = [];
        $deliveredAt = null;
        $readAt = null;
        foreach ($receipts as $receipt) {
            if (!$receipt instanceof MessageReceipt) {
                continue;
            }

            $recipientUser = $receipt->getRecipient();

            $receiptDeliveredAt = $receipt->getDeliveredAt();
            if ($receiptDeliveredAt instanceof \DateTimeImmutable) {
                $deliveredBy[] = $this->formatter->user($recipientUser);
                if ($deliveredAt === null || $receiptDeliveredAt < $deliveredAt) {
                    $deliveredAt = $receiptDeliveredAt;
                }
            }

            $receiptReadAt = $receipt->getReadAt();
            if ($receiptReadAt instanceof \DateTimeImmutable) {
                $readBy[] = $this->formatter->user($recipientUser);
                if ($readAt === null || $receiptReadAt < $readAt) {
                    $readAt = $receiptReadAt;
                }
            }
        }

        return [
            'id' => $message->getId()->toRfc4122(),
            'conversationId' => $conversation->getId()->toRfc4122(),
            'content' => $message->getContent(),
            'createdAt' => $message->getCreatedAt()->format(DATE_ATOM),
            'sender' => $this->formatter->user($message->getSender()),
            'isMine' => $message->getSender()->getId()->equals($viewer->getId()),
            'deliveredAt' => $deliveredAt?->format(DATE_ATOM),
            'readAt' => $readAt?->format(DATE_ATOM),
            'attachments' => array_map(fn (MessageAttachment $attachment): array => [
                'id' => $attachment->getId()->toRfc4122(),
                'url' => $this->absoluteUrl($attachment->getPath()),
                'name' => $attachment->getOriginalName(),
                'mimeType' => $attachment->getMimeType(),
                'size' => $attachment->getSize(),
            ], $message->getAttachments()->toArray()),
            'deliveredBy' => $deliveredBy,
            'readBy' => $readBy,
        ];
    }

    private function markDelivered(Conversation $conversation, User $viewer, ?\DateTimeImmutable $at = null): bool
    {
        $participant = $this->getParticipantRepository()->findOneByConversationAndUser($conversation, $viewer);
        if (!$participant instanceof ConversationParticipant) {
            return false;
        }

        $timestamp = $at ?? new \DateTimeImmutable();
        $changed = false;

        $previousParticipantDelivery = $participant->getLastDeliveredAt();
        $participant->markDelivered($timestamp);
        if ($previousParticipantDelivery === null || $previousParticipantDelivery < $timestamp) {
            $changed = true;
        }

        $pendingReceipts = $this->getReceiptRepository()->findPendingDeliveryForConversationAndUser($conversation, $viewer);
        foreach ($pendingReceipts as $receipt) {
            $receipt->markDelivered($timestamp);
            $changed = true;
        }

        return $changed;
    }

    private function initializeReceiptsForMessage(Message $message, Conversation $conversation, User $sender): void
    {
        foreach ($conversation->getParticipants() as $participant) {
            if (!$participant instanceof ConversationParticipant) {
                continue;
            }

            $recipient = $participant->getUser();
            if ($recipient->getId()->equals($sender->getId())) {
                continue;
            }

            $receipt = new MessageReceipt();
            $receipt
                ->setMessage($message)
                ->setRecipient($recipient);
            $this->entityManager->persist($receipt);
        }
    }

    private function absoluteUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return $path;
    }

    private function getConversationRepository(): ConversationRepository
    {
        $repository = $this->entityManager->getRepository(Conversation::class);
        if (!$repository instanceof ConversationRepository) {
            throw new \LogicException('Conversation repository is not configured correctly.');
        }

        return $repository;
    }

    private function getParticipantRepository(): ConversationParticipantRepository
    {
        $repository = $this->entityManager->getRepository(ConversationParticipant::class);
        if (!$repository instanceof ConversationParticipantRepository) {
            throw new \LogicException('Conversation participant repository is not configured correctly.');
        }

        return $repository;
    }

    private function getMessageRepository(): MessageRepository
    {
        $repository = $this->entityManager->getRepository(Message::class);
        if (!$repository instanceof MessageRepository) {
            throw new \LogicException('Message repository is not configured correctly.');
        }

        return $repository;
    }

    private function getReceiptRepository(): MessageReceiptRepository
    {
        $repository = $this->entityManager->getRepository(MessageReceipt::class);
        if (!$repository instanceof MessageReceiptRepository) {
            throw new \LogicException('Message receipt repository is not configured correctly.');
        }

        return $repository;
    }

    private function getUserRepository(): UserRepository
    {
        $repository = $this->entityManager->getRepository(User::class);
        if (!$repository instanceof UserRepository) {
            throw new \LogicException('User repository is not configured correctly.');
        }

        return $repository;
    }
}

