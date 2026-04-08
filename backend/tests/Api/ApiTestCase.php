<?php

declare(strict_types=1);

namespace App\Tests\Api;

use CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
    }

    protected function createAuthenticatedClient(string $emailPrefix = 'user'): array
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        $user = (new User())
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEmail(sprintf('%s_%s@example.test', $emailPrefix, bin2hex(random_bytes(3))))
            ->setPassword('test-password');

        $entityManager->persist($user);
        $entityManager->flush();

        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = $container->get(JWTTokenManagerInterface::class);
        $token = $jwtManager->create($user);

        $client->setServerParameters([
            'HTTP_Authorization' => 'Bearer ' . $token,
        ]);

        return [$client, $user];
    }

    protected function createUser(string $email): User
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = (new User())
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEmail($email)
            ->setPassword('test-password');

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    protected function authClientForUser(User $user): KernelBrowser
    {
        $client = static::createClient();
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $token = $jwtManager->create($user);
        $client->setServerParameters([
            'HTTP_Authorization' => 'Bearer ' . $token,
        ]);

        return $client;
    }

    private function resetDatabase(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if ($metadata === []) {
            return;
        }

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }
}

