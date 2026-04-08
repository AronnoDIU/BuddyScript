<?php

declare(strict_types=1);

namespace CoreBundle\Service;

use CoreBundle\Entity\Reaction as ReactionEntity;
use CoreBundle\Entity\User;
use CoreBundle\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class Reaction
{
    private readonly EntityManagerInterface $entityManager;

    private readonly ApiFormatter $formatter;

    public function __construct(EntityManagerInterface $entityManager, ApiFormatter $formatter)
    {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
    }

    /**
     * @return array<string,mixed>
     */
    public function catalog(): array
    {
        return [
            'targetTypes' => ['post', 'comment', 'reply'],
            'reactionTypes' => self::reactionTypes(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function toggle(User $user, string $targetType, string $targetId, string $reactionType): array
    {
        $uuid = $this->parseUuid($targetId);

        $repository = $this->getReactionRepository();
        $existing = $repository->findOneByTargetAndUser($targetType, $uuid, $user);

        $reacted = true;
        if ($existing instanceof ReactionEntity && $existing->getType() === $reactionType) {
            $this->entityManager->remove($existing);
            $reacted = false;
        } else {
            if ($existing instanceof ReactionEntity) {
                $existing->setType($reactionType);
            } else {
                $existing = new ReactionEntity();
                $existing
                    ->setUser($user)
                    ->setTargetType($targetType)
                    ->setTargetId($uuid)
                    ->setType($reactionType);
                $this->entityManager->persist($existing);
            }
        }

        $this->entityManager->flush();

        return $this->targetReactions($user, $targetType, $targetId, $reactionType, $reacted);
    }

    /**
     * @return array<string,mixed>
     */
    public function targetReactions(User $viewer, string $targetType, string $targetId, ?string $lastReaction = null, ?bool $reacted = null): array
    {
        $uuid = $this->parseUuid($targetId);
        $reactions = $this->getReactionRepository()->findByTarget($targetType, $uuid);

        $summary = array_fill_keys(self::reactionTypes(), 0);
        $recent = [];
        $myReaction = null;

        foreach ($reactions as $reaction) {
            $type = $reaction->getType();
            if (array_key_exists($type, $summary)) {
                $summary[$type]++;
            }

            if (count($recent) < 10) {
                $recent[] = [
                    'type' => $type,
                    'user' => $this->formatter->user($reaction->getUser()),
                ];
            }

            if ($reaction->getUser()->getId()->equals($viewer->getId())) {
                $myReaction = $type;
            }
        }

        return [
            'targetType' => $targetType,
            'targetId' => $targetId,
            'reacted' => $reacted,
            'lastReaction' => $lastReaction,
            'myReaction' => $myReaction,
            'total' => count($reactions),
            'summary' => $summary,
            'recent' => $recent,
        ];
    }

    /**
     * @return list<string>
     */
    public static function reactionTypes(): array
    {
        return [
            ReactionEntity::TYPE_LIKE,
            ReactionEntity::TYPE_LOVE,
            ReactionEntity::TYPE_HAHA,
            ReactionEntity::TYPE_WOW,
            ReactionEntity::TYPE_SAD,
            ReactionEntity::TYPE_ANGRY,
            ReactionEntity::TYPE_CARE,
        ];
    }

    private function parseUuid(string $value): Uuid
    {
        try {
            return Uuid::fromString($value);
        } catch (\InvalidArgumentException) {
            throw new \InvalidArgumentException('Invalid target id.');
        }
    }

    private function getReactionRepository(): ReactionRepository
    {
        $repository = $this->entityManager->getRepository(ReactionEntity::class);
        if (!$repository instanceof ReactionRepository) {
            throw new \LogicException('Reaction repository is not configured correctly.');
        }

        return $repository;
    }
}

