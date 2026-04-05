<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ApiFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly ApiFormatter $formatter,
    ) {
    }

    #[Route('/auth/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $constraints = new Assert\Collection([
            'firstName' => [new Assert\Required([new Assert\NotBlank(), new Assert\Length(max: 120)])],
            'lastName' => [new Assert\Required([new Assert\NotBlank(), new Assert\Length(max: 120)])],
            'email' => [new Assert\Required([new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)])],
            'password' => [new Assert\Required([new Assert\NotBlank(), new Assert\Length(min: 8, max: 255)])],
        ]);

        $errors = $this->validator->validate($payload, $constraints);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 422);
        }

        if ($this->userRepository->findOneBy(['email' => mb_strtolower(trim((string) $payload['email']))]) !== null) {
            return $this->json(['message' => 'Email is already in use.'], 409);
        }

        $user = (new User())
            ->setFirstName((string) $payload['firstName'])
            ->setLastName((string) $payload['lastName'])
            ->setEmail((string) $payload['email']);

        $user->setPassword($this->passwordHasher->hashPassword($user, (string) $payload['password']));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Registration successful.',
            'user' => $this->formatter->user($user),
        ], 201);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json(['user' => $this->formatter->user($user)]);
    }
}


