<?php

declare(strict_types=1);

namespace App\Tests\Api\V1;

use App\Tests\Api\ApiTestCase;
use CoreBundle\Entity\Post;
use CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class FeedDiscoveryControllerTest extends ApiTestCase
{
    public function testDiscoverySearchAndTrendingTopicsExposeOnlyPublicContent(): void
    {
        $viewer = $this->createUser('viewer@example.test');

        $author = $this->createUser('author@example.test');
        $author
            ->setFirstName('Discovery')
            ->setLastName('Author');
        $this->entityManager()->flush();

        $publicPost = $this->createPost(
            $author,
            'Discovery phase3 launch #phase3',
            Post::VISIBILITY_PUBLIC,
            '/uploads/posts/public-discovery.jpg',
            ['phase3', 'discovery'],
            ['discovery', 'launch']
        );

        $privatePost = $this->createPost(
            $author,
            'Discovery private behind the scenes #secret',
            Post::VISIBILITY_PRIVATE,
            '/uploads/posts/private-discovery.jpg',
            ['secret'],
            ['private', 'secret']
        );

        /** @var mixed $client */
        $client = $this->authClientForUser($viewer);

        $client->request('GET', '/api/v1/discover', ['limit' => 12]);
        $this->assertSameValue(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'Discovery response should be successful.');
        $discoverPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCountValue(1, $discoverPayload['stories'] ?? [], 'Discovery stories should contain the public post only.');
        $this->assertSameValue($publicPost->getId()->toRfc4122(), $discoverPayload['stories'][0]['post']['id'] ?? null, 'Discovery stories should return the public post.');
        $this->assertCountValue(1, $discoverPayload['reels'] ?? [], 'Discovery reels should contain the public post only.');
        $this->assertSameValue($publicPost->getId()->toRfc4122(), $discoverPayload['reels'][0]['post']['id'] ?? null, 'Discovery reels should return the public post.');
        $this->assertCountValue(1, $discoverPayload['live'] ?? [], 'Discovery live should contain the public post only.');
        $this->assertSameValue($publicPost->getId()->toRfc4122(), $discoverPayload['live'][0]['post']['id'] ?? null, 'Discovery live should return the public post.');

        $client->request('GET', '/api/v1/discover/search', ['q' => 'Discovery', 'limit' => 20]);
        $this->assertSameValue(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'Discovery search response should be successful.');
        $searchPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCountValue(1, $searchPayload['users'] ?? [], 'Discovery search should surface one matching author.');
        $this->assertSameValue('Discovery Author', $searchPayload['users'][0]['displayName'] ?? null, 'Discovery search should return the expected author.');
        $this->assertCountValue(1, $searchPayload['posts'] ?? [], 'Discovery search should surface one public post.');
        $this->assertSameValue($publicPost->getId()->toRfc4122(), $searchPayload['posts'][0]['id'] ?? null, 'Discovery search should return the public post.');
        $this->assertContainsValue('#phase3', $searchPayload['hashtags'] ?? [], 'Discovery search should include the public hashtag.');
        $this->assertNotContainsValue($privatePost->getId()->toRfc4122(), json_encode($searchPayload, JSON_THROW_ON_ERROR), 'Private posts must not leak into discovery search.');

        $client->request('GET', '/api/v1/discover/topics', ['limit' => 12]);
        $this->assertSameValue(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'Discovery topics response should be successful.');
        $topicsPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertContainsValue('#phase3', array_column($topicsPayload['topics'] ?? [], 'topic'), 'Trending topics should include the public hashtag.');
    }

    public function testDiscoveryRequiresAuthentication(): void
    {
        /** @var mixed $client */
        $client = static::createClient();
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');
        $client->request('GET', '/api/v1/discover');

        $this->assertSameValue(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode(), 'Discovery should require authentication.');
    }

    private function createPost(
        User $author,
        string $content,
        string $visibility,
        ?string $imagePath,
        array $hashtags = [],
        array $topics = [],
    ): Post {
        $post = (new Post())
            ->setAuthor($author)
            ->setContent($content)
            ->setVisibility($visibility)
            ->setImagePath($imagePath)
            ->setHashtags($hashtags)
            ->setTopics($topics);

        $this->entityManager()->persist($post);
        $this->entityManager()->flush();

        return $post;
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

    private function assertCountValue(int $expected, array $value, string $message): void
    {
        if (count($value) === $expected) {
            $this->addToAssertionCount(1);
            return;
        }

        throw new \RuntimeException($message . ' Expected count ' . $expected . ', got ' . count($value) . '.');
    }

    private function assertContainsValue(mixed $needle, array $haystack, string $message): void
    {
        if (in_array($needle, $haystack, true)) {
            $this->addToAssertionCount(1);
            return;
        }

        throw new \RuntimeException($message . ' Missing value ' . var_export($needle, true) . '.');
    }

    private function assertNotContainsValue(mixed $needle, string $haystack, string $message): void
    {
        if (!str_contains($haystack, (string) $needle)) {
            $this->addToAssertionCount(1);
            return;
        }

        throw new \RuntimeException($message . ' Unexpected value ' . var_export($needle, true) . '.');
    }
}

