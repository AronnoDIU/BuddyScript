<?php

declare(strict_types=1);

namespace App\Tests\Api\V1\Community;

use App\Tests\Api\ApiTestCase;
use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\Community\GroupMembership;
use CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class GroupControllerTest extends ApiTestCase
{
    public function testCreateGroup(): void
    {
        $creator = $this->createUser('creator@example.test');

        /** @var mixed $client */
        $client = $this->authClientForUser($creator);

        $client->request('POST', '/api/v1/groups', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test Group',
            'description' => 'A test group for Phase 4',
            'visibility' => Group::VISIBILITY_PUBLIC,
            'settings' => [
                'allow_member_posts' => true,
                'require_approval' => false,
                'enable_discussion' => true,
            ],
        ], JSON_THROW_ON_ERROR));

        $this->assertSameValue(Response::HTTP_CREATED, $client->getResponse()->getStatusCode(), 'Group creation should be successful.');
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotEmptyValue($payload['group']['id'] ?? null, 'Group ID should be returned.');
        $this->assertSameValue('Test Group', $payload['group']['name'] ?? null, 'Group name should match.');
        $this->assertSameValue('A test group for Phase 4', $payload['group']['description'] ?? null, 'Group description should match.');
        $this->assertSameValue(Group::VISIBILITY_PUBLIC, $payload['group']['visibility'] ?? null, 'Group visibility should match.');
        $this->assertSameValue(Group::ROLE_ADMIN, $payload['membership']['role'] ?? null, 'Creator should be admin.');
    }

    public function testCreateGroupRequiresAuthentication(): void
    {
        /** @var mixed $client */
        $client = static::createClient();
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');
        $client->request('POST', '/api/v1/groups');

        $this->assertSameValue(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode(), 'Group creation should require authentication.');
    }

    public function testListGroupsWithUser(): void
    {
        $user = $this->createUser('user@example.test');
        $creator = $this->createUser('creator@example.test');

        $group = $this->createGroup('Test Group', 'A test group', Group::VISIBILITY_PUBLIC, $creator);
        $this->createMembership($group, $user, Group::ROLE_MEMBER);

        /** @var mixed $client */
        $client = $this->authClientForUser($user);

        $client->request('GET', '/api/v1/groups');
        $this->assertSameValue(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'Group listing should be successful.');
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotEmptyValue($payload['groups'] ?? [], 'Groups should be returned.');
        $this->assertContainsValue($group->getId()->toRfc4122(), array_column($payload['groups'], 'id'), 'User group should be in the list.');
    }

    public function testGetGroup(): void
    {
        $creator = $this->createUser('creator@example.test');
        $user = $this->createUser('user@example.test');

        $group = $this->createGroup('Test Group', 'A test group', Group::VISIBILITY_PUBLIC, $creator);
        $this->createMembership($group, $user, Group::ROLE_MEMBER);

        /** @var mixed $client */
        $client = $this->authClientForUser($user);

        $client->request('GET', '/api/v1/groups/' . $group->getId()->toRfc4122());
        $this->assertSameValue(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'Group retrieval should be successful.');
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSameValue($group->getId()->toRfc4122(), $payload['group']['id'] ?? null, 'Group ID should match.');
        $this->assertSameValue('Test Group', $payload['group']['name'] ?? null, 'Group name should match.');
        $this->assertSameValue(Group::ROLE_MEMBER, $payload['group']['userRole'] ?? null, 'User role should be member.');
    }

    public function testJoinGroup(): void
    {
        $creator = $this->createUser('creator@example.test');
        $user = $this->createUser('user@example.test');

        $group = $this->createGroup('Test Group', 'A test group', Group::VISIBILITY_PUBLIC, $creator);

        /** @var mixed $client */
        $client = $this->authClientForUser($user);

        $client->request('POST', '/api/v1/groups/' . $group->getId()->toRfc4122() . '/join');
        $this->assertSameValue(Response::HTTP_CREATED, $client->getResponse()->getStatusCode(), 'Group joining should be successful.');
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSameValue(Group::ROLE_MEMBER, $payload['membership']['role'] ?? null, 'User should be member after joining.');
    }

    public function testLeaveGroup(): void
    {
        $creator = $this->createUser('creator@example.test');
        $user = $this->createUser('user@example.test');

        $group = $this->createGroup('Test Group', 'A test group', Group::VISIBILITY_PUBLIC, $creator);
        $this->createMembership($group, $user, Group::ROLE_MEMBER);

        /** @var mixed $client */
        $client = $this->authClientForUser($user);

        $client->request('POST', '/api/v1/groups/' . $group->getId()->toRfc4122() . '/leave');
        $this->assertSameValue(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'Group leaving should be successful.');
    }

    public function testGetGroupMembers(): void
    {
        $creator = $this->createUser('creator@example.test');
        $user = $this->createUser('user@example.test');
        $moderator = $this->createUser('moderator@example.test');

        $group = $this->createGroup('Test Group', 'A test group', Group::VISIBILITY_PUBLIC, $creator);
        $this->createMembership($group, $user, Group::ROLE_MEMBER);
        $this->createMembership($group, $moderator, Group::ROLE_MODERATOR);

        /** @var mixed $client */
        $client = $this->authClientForUser($creator);

        $client->request('GET', '/api/v1/groups/' . $group->getId()->toRfc4122() . '/members');
        $this->assertSameValue(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'Member listing should be successful.');
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCountValue(3, $payload['members'] ?? [], 'All members should be returned.');
        $roles = array_column($payload['members'], 'role');
        $this->assertContainsValue(Group::ROLE_ADMIN, $roles, 'Admin role should be present.');
        $this->assertContainsValue(Group::ROLE_MODERATOR, $roles, 'Moderator role should be present.');
        $this->assertContainsValue(Group::ROLE_MEMBER, $roles, 'Member role should be present.');
    }

    private function createGroup(string $name, string $description, string $visibility, User $creator): Group
    {
        $group = (new Group())
            ->setName($name)
            ->setDescription($description)
            ->setVisibility($visibility)
            ->setCreator($creator);

        $this->entityManager()->persist($group);
        $this->entityManager()->flush();

        // Add creator as admin
        $membership = new GroupMembership();
        $membership
            ->setGroup($group)
            ->setUser($creator)
            ->setRole(Group::ROLE_ADMIN);
        $this->entityManager()->persist($membership);
        $this->entityManager()->flush();

        return $group;
    }

    private function createMembership(Group $group, User $user, string $role): GroupMembership
    {
        $membership = new GroupMembership();
        $membership
            ->setGroup($group)
            ->setUser($user)
            ->setRole($role);

        $this->entityManager()->persist($membership);
        $this->entityManager()->flush();

        return $membership;
    }

    private function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function assertSameValue(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected === $actual) {
            $this->addToAssertionCount(1);
            return;
        }

        throw new \RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
    }

    private function assertNotEmptyValue(mixed $value, string $message): void
    {
        if (!empty($value)) {
            $this->addToAssertionCount(1);
            return;
        }

        throw new \RuntimeException($message . ' Value should not be empty.');
    }

    private function assertContainsValue(mixed $needle, array $haystack, string $message): void
    {
        if (in_array($needle, $haystack, true)) {
            $this->addToAssertionCount(1);
            return;
        }

        throw new \RuntimeException($message . ' Missing value ' . var_export($needle, true) . '.');
    }

    private function assertCountValue(int $expected, array $value, string $message): void
    {
        if (count($value) === $expected) {
            $this->addToAssertionCount(1);
            return;
        }

        throw new \RuntimeException($message . ' Expected count ' . $expected . ', got ' . count($value) . '.');
    }
}
