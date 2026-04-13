<?php

declare(strict_types=1);

namespace App\Tests\Api\V1;

use App\Tests\Api\ApiTestCase;
use CoreBundle\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class FeedControllerTest extends ApiTestCase
{
    public function testOwnerCanDeleteOwnPost(): void
    {
        [$client, $owner] = $this->createAuthenticatedClient('feed_owner');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $post = (new Post())
            ->setAuthor($owner)
            ->setContent('Delete me')
            ->setVisibility(Post::VISIBILITY_PUBLIC);

        $entityManager->persist($post);
        $entityManager->flush();

        $client->request('DELETE', '/api/v1/posts/' . $post->getId()->toRfc4122());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Post deleted successfully.', $payload['message'] ?? null);

        $entityManager->clear();
        self::assertNull($entityManager->getRepository(Post::class)->find($post->getId()));
    }

    public function testUserCannotDeleteAnotherUsersPost(): void
    {
        $author = $this->createUser('feed_author@example.test');
        $other = $this->createUser('feed_other@example.test');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $post = (new Post())
            ->setAuthor($author)
            ->setContent('You cannot delete this')
            ->setVisibility(Post::VISIBILITY_PUBLIC);

        $entityManager->persist($post);
        $entityManager->flush();

        $client = $this->authClientForUser($other);
        $client->request('DELETE', '/api/v1/posts/' . $post->getId()->toRfc4122());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $entityManager->clear();
        self::assertNotNull($entityManager->getRepository(Post::class)->find($post->getId()));
    }
}

