<?php

declare(strict_types=1);

namespace App\Tests\Api\V1\Community;

use App\Tests\Api\ApiTestCase;
use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\Community\GroupPost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class GroupPostControllerTest extends ApiTestCase
{
    public function testCanListGroupPosts(): void
    {
        $author = $this->createUser('group_list_author@example.test');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $group = (new Group())
            ->setName('List Group')
            ->setVisibility(Group::VISIBILITY_PUBLIC)
            ->setCreator($author);

        $post = (new GroupPost())
            ->setGroup($group)
            ->setAuthor($author)
            ->setContent('Timeline content');

        $entityManager->persist($group);
        $entityManager->persist($post);
        $entityManager->flush();

        $client = $this->authClientForUser($author);
        $client->request('GET', '/api/v1/groups/' . $group->getId()->toRfc4122() . '/posts?limit=20');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload['posts'] ?? null);
        self::assertSame('Timeline content', $payload['posts'][0]['content'] ?? null);
    }

    public function testCanSearchGroupPosts(): void
    {
        $author = $this->createUser('group_search_author@example.test');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $group = (new Group())
            ->setName('Search Group')
            ->setVisibility(Group::VISIBILITY_PUBLIC)
            ->setCreator($author);

        $post = (new GroupPost())
            ->setGroup($group)
            ->setAuthor($author)
            ->setContent('Symfony search keyword');

        $entityManager->persist($group);
        $entityManager->persist($post);
        $entityManager->flush();

        $client = $this->authClientForUser($author);
        $client->request('GET', '/api/v1/groups/' . $group->getId()->toRfc4122() . '/posts?q=symfony&limit=20');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('symfony', $payload['query'] ?? null);
        self::assertIsArray($payload['posts'] ?? null);
        self::assertNotEmpty($payload['posts'] ?? []);
    }

    public function testOwnerCanDeleteOwnGroupPost(): void
    {
        $author = $this->createUser('group_author@example.test');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $group = (new Group())
            ->setName('Delete Test Group')
            ->setVisibility(Group::VISIBILITY_PUBLIC)
            ->setCreator($author);

        $post = (new GroupPost())
            ->setGroup($group)
            ->setAuthor($author)
            ->setContent('Author-owned group post');

        $entityManager->persist($group);
        $entityManager->persist($post);
        $entityManager->flush();

        $client = $this->authClientForUser($author);
        $client->request('DELETE', '/api/v1/group-posts/' . $post->getId()->toRfc4122());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Post deleted successfully.', $payload['message'] ?? null);

        $entityManager->clear();
        self::assertNull($entityManager->getRepository(GroupPost::class)->find($post->getId()));
    }

    public function testUserCannotDeleteAnotherUsersGroupPost(): void
    {
        $author = $this->createUser('group_author_block@example.test');
        $other = $this->createUser('group_other_block@example.test');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $group = (new Group())
            ->setName('Delete Permission Group')
            ->setVisibility(Group::VISIBILITY_PUBLIC)
            ->setCreator($author);

        $post = (new GroupPost())
            ->setGroup($group)
            ->setAuthor($author)
            ->setContent('Protected group post');

        $entityManager->persist($group);
        $entityManager->persist($post);
        $entityManager->flush();

        $client = $this->authClientForUser($other);
        $client->request('DELETE', '/api/v1/group-posts/' . $post->getId()->toRfc4122());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $entityManager->clear();
        self::assertNotNull($entityManager->getRepository(GroupPost::class)->find($post->getId()));
    }
}

