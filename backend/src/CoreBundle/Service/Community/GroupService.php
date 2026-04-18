<?php

declare(strict_types=1);

namespace CoreBundle\Service\Community;

use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\Community\GroupMembership;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\GroupMembershipRepository;
use CoreBundle\Repository\Community\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

readonly class GroupService
{
    private EntityManagerInterface $entityManager;
    private string $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir
    ) {
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    /**
     * @return array<string,mixed>
     */
    public function createGroup(
        User $creator,
        string $name,
        string $description,
        string $visibility,
        ?UploadedFile $avatar,
        array $settings
    ): array {
        $group = new Group();
        $group
            ->setName($name)
            ->setDescription($description ?: null)
            ->setVisibility($visibility)
            ->setCreator($creator)
            ->setSettings($settings);

        if ($avatar instanceof UploadedFile) {
            $path = $this->storeGroupAvatar($avatar);
            if ($path === null) {
                throw new \InvalidArgumentException('Invalid avatar upload.');
            }
            $group->setAvatarPath($path);
        }

        $this->entityManager->persist($group);

        // Add creator as admin
        $membership = new GroupMembership();
        $membership
            ->setGroup($group)
            ->setUser($creator)
            ->setRole(Group::ROLE_ADMIN);
        $this->entityManager->persist($membership);

        $this->entityManager->flush();

        return [
            'group' => $this->formatGroup($group, $creator),
            'membership' => $this->formatMembership($membership, $creator),
        ];
    }

    /**
     * @return list<Group>
     */
    public function getGroupsForUser(User $user, int $limit = 20): array
    {
        return $this->getGroupRepository()->findGroupsForUser($user, $limit);
    }

    /**
     * @return list<Group>
     */
    public function getPublicGroups(int $limit = 50): array
    {
        return $this->getGroupRepository()->findPublicGroups($limit);
    }

    /**
     * @return list<Group>
     */
    public function searchGroups(User $user, string $query, int $limit = 20): array
    {
        return $this->getGroupRepository()->searchGroups($query, $user, $limit);
    }

    public function getAccessibleGroup(string $id, User $user): ?Group
    {
        return $this->getGroupRepository()->findAccessibleForUser($id, $user);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function updateGroup(
        User $user,
        string $id,
        string $name,
        string $description,
        string $visibility,
        ?UploadedFile $avatar,
        array $settings
    ): ?array {
        $group = $this->getGroupRepository()->findAccessibleForUser($id, $user);
        if (!$group instanceof Group) {
            return null;
        }

        if (!$group->hasPermission($user, 'admin')) {
            return null;
        }

        $group
            ->setName($name)
            ->setDescription($description ?: null)
            ->setVisibility($visibility)
            ->setSettings($settings);

        if ($avatar instanceof UploadedFile) {
            $path = $this->storeGroupAvatar($avatar);
            if ($path === null) {
                throw new \InvalidArgumentException('Invalid avatar upload.');
            }
            $group->setAvatarPath($path);
        }

        $this->entityManager->flush();

        return ['group' => $this->formatGroup($group, $user)];
    }

    /** @return array<string,mixed>|null */
    public function deleteGroup(User $user, string $id): ?array
    {
        $group = $this->getGroupRepository()->findAccessibleForUser($id, $user);
        if (!$group instanceof Group) {
            return null;
        }

        if (!$group->hasPermission($user, 'admin')) {
            return null;
        }

        $this->entityManager->remove($group);
        $this->entityManager->flush();

        return ['message' => 'Group deleted successfully.'];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function joinGroup(User $user, string $id): ?array
    {
        $group = $this->getGroupRepository()->findAccessibleForUser($id, $user);
        if (!$group instanceof Group) {
            return null;
        }

        if ($group->getVisibility() === Group::VISIBILITY_SECRET) {
            throw new \InvalidArgumentException('Cannot join secret groups.');
        }

        $existingMembership = $this->getGroupMembershipRepository()->findByUserAndGroup($user, $group);
        if ($existingMembership instanceof GroupMembership) {
            throw new \InvalidArgumentException('Already a member of this group.');
        }

        $membership = new GroupMembership();
        $membership
            ->setGroup($group)
            ->setUser($user)
            ->setRole(Group::ROLE_MEMBER);
        $this->entityManager->persist($membership);

        $this->entityManager->flush();

        return [
            'membership' => $this->formatMembership($membership, $user),
            'group' => $this->formatGroup($group, $user),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function leaveGroup(User $user, string $id): ?array
    {
        $group = $this->getGroupRepository()->findAccessibleForUser($id, $user);
        if (!$group instanceof Group) {
            return null;
        }

        $membership = $this->getGroupMembershipRepository()->findByUserAndGroup($user, $group);
        if (!$membership instanceof GroupMembership) {
            throw new \InvalidArgumentException('Not a member of this group.');
        }

        if ($membership->isAdmin()) {
            $adminCount = $this->getGroupMembershipRepository()->countMembersByRole($group, Group::ROLE_ADMIN);
            if ($adminCount <= 1) {
                throw new \InvalidArgumentException('Cannot leave group as the only admin.');
            }
        }

        $this->entityManager->remove($membership);
        $this->entityManager->flush();

        return ['message' => 'Left group successfully.'];
    }

    /**
     * @return list<GroupMembership>
     */
    public function getGroupMembers(Group $group, ?string $role, int $limit = 20): array
    {
        if ($role !== null) {
            return $this->getGroupMembershipRepository()->findByRole($group, $role, $limit);
        }
        return $this->getGroupMembershipRepository()->findByGroup($group, $limit);
    }

    /**
     * @param User $admin
     * @param string $groupId
     * @param string $userId
     * @param string $role
     * @return array<string,mixed>|null
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateMemberRole(User $admin, string $groupId, string $userId, string $role): ?array
    {
        $group = $this->getGroupRepository()->findAccessibleForUser($groupId, $admin);
        if (!$group instanceof Group) {
            return null;
        }

        if (!$group->hasPermission($admin, 'admin')) {
            return null;
        }

        try {
            $targetUser = $this->entityManager->find(User::class, Uuid::fromString($userId));
        } catch (\InvalidArgumentException) {
            return null;
        }

        if (!$targetUser instanceof User) {
            return null;
        }

        $membership = $this->getGroupMembershipRepository()->findByUserAndGroup($targetUser, $group);
        if (!$membership instanceof GroupMembership) {
            return null;
        }

        // Prevent removing the last admin
        if ($membership->isAdmin() && $role !== Group::ROLE_ADMIN) {
            $adminCount = $this->getGroupMembershipRepository()->countMembersByRole($group, Group::ROLE_ADMIN);
            if ($adminCount <= 1) {
                throw new \InvalidArgumentException('Cannot remove the last admin.');
            }
        }

        $membership->setRole($role);
        $this->entityManager->flush();

        return [
            'membership' => $this->formatMembership($membership, $admin),
            'group' => $this->formatGroup($group, $admin),
        ];
    }

    /**
     * @param User $admin
     * @param string $groupId
     * @param string $userId
     * @return array<string,mixed>|null
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeMember(User $admin, string $groupId, string $userId): ?array
    {
        $group = $this->getGroupRepository()->findAccessibleForUser($groupId, $admin);
        if (!$group instanceof Group) {
            return null;
        }

        if (!$group->hasPermission($admin, 'admin')) {
            return null;
        }

        try {
            $targetUser = $this->entityManager->find(User::class, Uuid::fromString($userId));
        } catch (\InvalidArgumentException) {
            return null;
        }

        if (!$targetUser instanceof User) {
            return null;
        }

        $membership = $this->getGroupMembershipRepository()->findByUserAndGroup($targetUser, $group);
        if (!$membership instanceof GroupMembership) {
            return null;
        }

        // Prevent removing the last admin
        if ($membership->isAdmin()) {
            $adminCount = $this->getGroupMembershipRepository()->countMembersByRole($group, Group::ROLE_ADMIN);
            if ($adminCount <= 1) {
                throw new \InvalidArgumentException('Cannot remove the last admin.');
            }
        }

        $this->entityManager->remove($membership);
        $this->entityManager->flush();

        return ['message' => 'Member removed successfully.'];
    }

    private function storeGroupAvatar(UploadedFile $file): ?string
    {
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false || !isset($imageInfo[2])) {
            return null;
        }

        $extensionMap = [
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];

        $extension = $extensionMap[$imageInfo[2]] ?? null;
        if ($extension === null) {
            return null;
        }

        if ($file->getSize() !== null && $file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        $uploadDir = $this->projectDir . '/public/uploads/groups';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadDir));
        }

        $name = Uuid::v7()->toRfc4122() . '.' . $extension;
        $file->move($uploadDir, $name);

        return '/uploads/groups/' . $name;
    }

    /**
     * @return array<string,mixed>
     */
    private function formatGroup(Group $group, User $viewer): array
    {
        return [
            'id' => $group->getId()->toRfc4122(),
            'name' => $group->getName(),
            'description' => $group->getDescription(),
            'avatarPath' => $group->getAvatarPath(),
            'visibility' => $group->getVisibility(),
            'settings' => $group->getSettings(),
            'createdAt' => $group->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'creator' => [
                'id' => $group->getCreator()->getId()->toRfc4122(),
                'username' => $group->getCreator()->getUsername(),
                'displayName' => $group->getCreator()->getFirstName() . ' ' . $group->getCreator()->getLastName(),
            ],
            'memberCount' => $group->getMemberCount(),
            'postCount' => $group->getPostCount(),
            'userRole' => $group->getMembershipRole($viewer),
            'permissions' => [
                'view' => $group->hasPermission($viewer, 'view'),
                'post' => $group->hasPermission($viewer, 'post'),
                'moderate' => $group->hasPermission($viewer, 'moderate'),
                'admin' => $group->hasPermission($viewer, 'admin'),
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function formatMembership(GroupMembership $membership, User $viewer): array
    {
        return [
            'id' => $membership->getId()->toRfc4122(),
            'role' => $membership->getRole(),
            'joinedAt' => $membership->getJoinedAt()->format(\DateTimeInterface::ATOM),
            'user' => [
                'id' => $membership->getUser()->getId()->toRfc4122(),
                'username' => $membership->getUser()->getUsername(),
                'displayName' => $membership->getUser()->getFirstName() . ' ' . $membership->getUser()->getLastName(),
            ],
        ];
    }

    private function getGroupRepository(): GroupRepository
    {
        $repository = $this->entityManager->getRepository(Group::class);
        if (!$repository instanceof GroupRepository) {
            throw new \LogicException('Group repository is not configured correctly.');
        }
        return $repository;
    }

    private function getGroupMembershipRepository(): GroupMembershipRepository
    {
        $repository = $this->entityManager->getRepository(GroupMembership::class);
        if (!$repository instanceof GroupMembershipRepository) {
            throw new \LogicException('GroupMembership repository is not configured correctly.');
        }
        return $repository;
    }
}
