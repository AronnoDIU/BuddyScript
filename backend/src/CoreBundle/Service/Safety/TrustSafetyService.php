<?php

declare(strict_types=1);

namespace CoreBundle\Service\Safety;

use CoreBundle\Entity\Safety\Report;
use CoreBundle\Entity\Safety\UserBlock;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Safety\ReportRepository;
use CoreBundle\Repository\Safety\UserBlockRepository;
use Doctrine\ORM\EntityManagerInterface;

class TrustSafetyService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function submitReport(User $reporter, array $payload): array
    {
        $report = (new Report())
            ->setReporter($reporter)
            ->setTargetType((string) $payload['targetType'])
            ->setTargetId((string) $payload['targetId'])
            ->setCategory((string) $payload['category'])
            ->setReason((string) $payload['reason']);

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        return ['report' => $this->formatReport($report)];
    }

    /**
     * @return array<string, mixed>
     */
    public function myReports(User $reporter): array
    {
        return [
            'reports' => array_map(
                fn (Report $report): array => $this->formatReport($report),
                $this->getReportRepository()->findByReporter($reporter, 200)
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function blockUser(User $blocker, User $blocked): array
    {
        if ($blocker->getId()->equals($blocked->getId())) {
            throw new \InvalidArgumentException('You cannot block yourself.');
        }

        $existing = $this->getUserBlockRepository()->findByUsers($blocker, $blocked);
        if (!$existing instanceof UserBlock) {
            $block = (new UserBlock())
                ->setBlocker($blocker)
                ->setBlocked($blocked);
            $this->entityManager->persist($block);
            $this->entityManager->flush();

            return ['block' => $this->formatBlock($block)];
        }

        return ['block' => $this->formatBlock($existing)];
    }

    /**
     * @return array<string, mixed>
     */
    public function unblockUser(User $blocker, User $blocked): array
    {
        $existing = $this->getUserBlockRepository()->findByUsers($blocker, $blocked);
        if ($existing instanceof UserBlock) {
            $this->entityManager->remove($existing);
            $this->entityManager->flush();
        }

        return ['message' => 'User unblocked successfully.'];
    }

    /**
     * @return array<string, mixed>
     */
    public function blockedUsers(User $blocker): array
    {
        return [
            'blockedUsers' => array_map(
                fn (UserBlock $block): array => $this->formatBlock($block),
                $this->getUserBlockRepository()->findByBlocker($blocker, 500)
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatReport(Report $report): array
    {
        return [
            'id' => $report->getId()->toRfc4122(),
            'targetType' => $report->getTargetType(),
            'targetId' => $report->getTargetId(),
            'category' => $report->getCategory(),
            'reason' => $report->getReason(),
            'status' => $report->getStatus(),
            'resolutionNote' => $report->getResolutionNote(),
            'createdAt' => $report->getCreatedAt()->format(DATE_ATOM),
            'resolvedAt' => $report->getResolvedAt()?->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBlock(UserBlock $block): array
    {
        return [
            'id' => $block->getId()->toRfc4122(),
            'createdAt' => $block->getCreatedAt()->format(DATE_ATOM),
            'blockedUser' => [
                'id' => $block->getBlocked()->getId()->toRfc4122(),
                'displayName' => $block->getBlocked()->getDisplayName(),
                'email' => $block->getBlocked()->getEmail(),
            ],
        ];
    }

    private function getReportRepository(): ReportRepository
    {
        $repository = $this->entityManager->getRepository(Report::class);
        if (!$repository instanceof ReportRepository) {
            throw new \LogicException('Safety report repository is not configured correctly.');
        }

        return $repository;
    }

    private function getUserBlockRepository(): UserBlockRepository
    {
        $repository = $this->entityManager->getRepository(UserBlock::class);
        if (!$repository instanceof UserBlockRepository) {
            throw new \LogicException('User block repository is not configured correctly.');
        }

        return $repository;
    }
}

