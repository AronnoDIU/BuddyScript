<?php

namespace ApiBundle\Controller;

use ApiBundle\Exception\ValidationException;
use ApiBundle\Validation\MessengerValidator;
use CoreBundle\Entity\User;
use CoreBundle\Repository\UserRepository;
use CoreBundle\Service\Messenger as MessengerService;
use CoreBundle\Service\MessengerStreamToken;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class MessengerController extends BaseController
{
    private readonly MessengerService $messengerService;

    private readonly MessengerValidator $messengerValidator;

    private readonly MessengerStreamToken $streamTokenService;

    private readonly UserRepository $userRepository;

    public function __construct(
        MessengerService $messengerService,
        MessengerValidator $messengerValidator,
        MessengerStreamToken $streamTokenService,
        UserRepository $userRepository,
    )
    {
        parent::__construct();
        $this->messengerService = $messengerService;
        $this->messengerValidator = $messengerValidator;
        $this->streamTokenService = $streamTokenService;
        $this->userRepository = $userRepository;
    }

    #[Route('/messenger/conversations', name: 'api_messenger_conversations', methods: ['GET'])]
    public function conversations(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $query = (string) $request->query->get('q', '');
        $offset = max(0, (int) $request->query->get('offset', 0));
        $limit = max(1, min(100, (int) $request->query->get('limit', 40)));
        $includeArchived = filter_var((string) $request->query->get('includeArchived', '0'), FILTER_VALIDATE_BOOLEAN);

        return $this->json($this->messengerService->conversations($user, $query, $offset, $limit, $includeArchived));
    }

    #[Route('/messenger/stream-token', name: 'api_messenger_stream_token', methods: ['POST'])]
    public function streamToken(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json($this->streamTokenService->issue($user, 120));
    }

    #[Route('/messenger/conversations/{id}/messages', name: 'api_messenger_messages', methods: ['GET'])]
    public function messages(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->messengerValidator->setAction('messages')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = min(200, (int)$request->query->get('limit', 80))
                    |> (static fn($x) => max(1, $x))
                    |> (fn($x) => $this->messengerService->messages($user, $id, (string)$request->query->get('before', ''), $x,));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Conversation not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/messenger/messages', name: 'api_messenger_send', methods: ['POST'])]
    public function send(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->extractPayload($request);

        try {
            $this->messengerValidator->setAction('send_message')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->messengerService->sendMessage(
                $user,
                isset($payload['conversationId']) ? (string) $payload['conversationId'] : null,
                isset($payload['recipientId']) ? (string) $payload['recipientId'] : null,
                isset($payload['content']) ? (string) $payload['content'] : null,
                $request->files->get('attachment') instanceof UploadedFile ? $request->files->get('attachment') : null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result, 201);
    }

    #[Route('/messenger/conversations/{id}/read', name: 'api_messenger_mark_read', methods: ['POST'])]
    public function markRead(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->messengerValidator->setAction('mark_read')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->messengerService->markRead($user, $id);
        if ($result === null) {
            return $this->json(['message' => 'Conversation not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/messenger/conversations/{id}/pin', name: 'api_messenger_pin', methods: ['POST'])]
    public function pin(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->extractPayload($request);
        $payload['id'] = $id;

        try {
            $this->messengerValidator->setAction('pin')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $pinned = filter_var((string) ($payload['pinned'] ?? '1'), FILTER_VALIDATE_BOOLEAN);

        $result = $this->messengerService->pinConversation($user, $id, $pinned);
        if ($result === null) {
            return $this->json(['message' => 'Conversation not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/messenger/conversations/{id}/mute', name: 'api_messenger_mute', methods: ['POST'])]
    public function mute(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->extractPayload($request);
        $payload['id'] = $id;

        try {
            $this->messengerValidator->setAction('mute')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $minutes = max(0, min(10080, (int) ($payload['minutes'] ?? 60)));

        $result = $this->messengerService->muteConversation($user, $id, $minutes);
        if ($result === null) {
            return $this->json(['message' => 'Conversation not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/messenger/conversations/{id}/archive', name: 'api_messenger_archive', methods: ['POST'])]
    public function archive(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->extractPayload($request);
        $payload['id'] = $id;

        try {
            $this->messengerValidator->setAction('archive')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $archived = filter_var((string) ($payload['archived'] ?? '1'), FILTER_VALIDATE_BOOLEAN);

        $result = $this->messengerService->archiveConversation($user, $id, $archived);
        if ($result === null) {
            return $this->json(['message' => 'Conversation not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/messenger/updates', name: 'api_messenger_updates', methods: ['GET'])]
    public function updates(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $result = $this->messengerService->updates($user, (string) $request->query->get('since', ''));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result);
    }

    #[Route('/messenger/stream', name: 'api_messenger_stream', methods: ['GET'])]
    public function streamUpdates(Request $request, #[CurrentUser] ?User $user): StreamedResponse|JsonResponse
    {
        $streamUser = $this->resolveStreamUser($request, $user);
        if (!$streamUser instanceof User) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $conversationId = trim((string) $request->query->get('conversationId', ''));

        $response = new StreamedResponse(function () use ($request, $streamUser, $conversationId): void {
            @set_time_limit(0);
            $since = trim((string) $request->query->get('since', ''));

            for ($tick = 0; $tick < 20; $tick++) {
                $payload = $this->messengerService->updates($streamUser, $since);
                $updates = (array) ($payload['updates'] ?? []);
                if ($conversationId !== '') {
                    $updates = array_values(array_filter($updates, static fn (array $item): bool => (string) ($item['conversationId'] ?? '') === $conversationId));
                }

                $since = (string) ($payload['serverTime'] ?? $since);

                echo 'event: updates' . "\n";
                echo 'data: ' . json_encode([
                    'updates' => $updates,
                    'serverTime' => $since,
                ], JSON_THROW_ON_ERROR) . "\n\n";
                @ob_flush();
                @flush();

                sleep(1);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    /**
     * @return array<string,mixed>
     */
    private function extractPayload(Request $request): array
    {
        $payload = $this->combineRequestData($request);
        if ($payload !== []) {
            return $payload;
        }

        $content = trim($request->getContent());
        if ($content === '') {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveStreamUser(Request $request, ?User $currentUser): ?User
    {
        if ($currentUser instanceof User) {
            return $currentUser;
        }

        $token = trim((string) $request->query->get('streamToken', ''));
        if ($token === '') {
            return null;
        }

        $userId = $this->streamTokenService->resolveUserId($token);
        if ($userId === null) {
            return null;
        }

        return $this->userRepository->findOneById($userId);
    }
}
