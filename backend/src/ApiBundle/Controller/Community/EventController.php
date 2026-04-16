<?php

declare(strict_types=1);

namespace ApiBundle\Controller\Community;

use ApiBundle\Controller\BaseController;
use CoreBundle\Entity\Community\Event;
use CoreBundle\Entity\Community\EventMembership;
use CoreBundle\Entity\Community\EventPost;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\EventMembershipRepository;
use CoreBundle\Repository\Community\EventPostRepository;
use CoreBundle\Repository\Community\EventRepository;
use CoreBundle\Service\ApiFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

class EventController extends BaseController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiFormatter $formatter,
    ) {
        parent::__construct();
    }

    #[Route('/events', name: 'api_events_list', methods: ['GET'])]
    public function listEvents(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $query = trim((string) $request->query->get('q', ''));

        if ($query !== '') {
            $events = $this->getEventRepository()->searchEvents($query, $user, $limit);
        } else {
            $events = $this->getEventRepository()->findEventsForUser($user, $limit);
        }

        return $this->json([
            'events' => array_map(fn (Event $event): array => $this->formatter->event($event, $user), $events),
            'query' => $query,
        ]);
    }

    #[Route('/events/public', name: 'api_events_public', methods: ['GET'])]
    public function listPublicEvents(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $query = trim((string) $request->query->get('q', ''));

        $events = $query !== ''
            ? array_filter(
                $this->getEventRepository()->findUpcomingEvents($limit * 2),
                fn (Event $event): bool => mb_stripos($event->getName() . ' ' . ($event->getDescription() ?? '') . ' ' . ($event->getLocation() ?? ''), $query) !== false,
            )
            : $this->getEventRepository()->findUpcomingEvents($limit);

        return $this->json([
            'events' => array_map(fn (Event $event): array => $this->formatter->event($event, $user), array_slice(array_values($events), 0, $limit)),
        ]);
    }

    #[Route('/events', name: 'api_events_create', methods: ['POST'])]
    public function createEvent(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->json(['message' => 'Event name is required.'], 422);
        }

        try {
            $startDate = new \DateTimeImmutable((string) ($payload['startDate'] ?? 'now'));
            $endDate = new \DateTimeImmutable((string) ($payload['endDate'] ?? '+1 hour'));
        } catch (\Throwable) {
            return $this->json(['message' => 'Invalid start or end date.'], 422);
        }

        if ($endDate < $startDate) {
            return $this->json(['message' => 'End date must be after start date.'], 422);
        }

        $event = new Event();
        try {
            $event
                ->setName($name)
                ->setDescription(isset($payload['description']) ? (string) $payload['description'] : null)
                ->setType((string) ($payload['type'] ?? Event::TYPE_OFFLINE))
                ->setStatus((string) ($payload['status'] ?? Event::STATUS_UPCOMING))
                ->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setLocation(isset($payload['location']) ? (string) $payload['location'] : null)
                ->setOnlineUrl(isset($payload['onlineUrl']) ? (string) $payload['onlineUrl'] : null)
                ->setMaxAttendees((int) ($payload['maxAttendees'] ?? 0))
                ->setCreator($user)
                ->setSettings($this->normalizeEventSettings($payload['settings'] ?? []));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        $avatar = $request->files->get('avatar');
        if ($avatar instanceof UploadedFile) {
            $path = $this->storeImage($avatar, 'events');
            if ($path === null) {
                return $this->json(['message' => 'Invalid avatar upload.'], 422);
            }
            $event->setAvatarPath($path);
        }

        $this->entityManager->persist($event);

        $membership = new EventMembership();
        $membership
            ->setEvent($event)
            ->setUser($user)
            ->setRole(Event::ROLE_ORGANIZER);
        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        return $this->json([
            'event' => $this->formatter->event($event, $user),
            'membership' => $this->formatter->eventMembership($membership, $user),
        ], 201);
    }

    #[Route('/events/{id}', name: 'api_events_get', methods: ['GET'])]
    public function getEvent(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $event = $this->findEvent($id);
        if (!$event instanceof Event) {
            return $this->json(['message' => 'Event not found.'], 404);
        }

        return $this->json(['event' => $this->formatter->event($event, $user)]);
    }

    #[Route('/events/{id}/join', name: 'api_events_join', methods: ['POST'])]
    public function joinEvent(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $event = $this->findEvent($id);
        if (!$event instanceof Event) {
            return $this->json(['message' => 'Event not found.'], 404);
        }

        if ($event->isFull()) {
            return $this->json(['message' => 'Event is full.'], 422);
        }

        $membership = $this->getEventMembershipRepository()->findByUserAndEvent($user, $event);
        if (!$membership instanceof EventMembership) {
            $membership = new EventMembership();
            $membership
                ->setEvent($event)
                ->setUser($user)
                ->setRole(Event::ROLE_ATTENDEE);
            $this->entityManager->persist($membership);
            $this->entityManager->flush();
        }

        return $this->json([
            'event' => $this->formatter->event($event, $user),
            'membership' => $this->formatter->eventMembership($membership, $user),
        ]);
    }

    #[Route('/events/{id}/leave', name: 'api_events_leave', methods: ['POST'])]
    public function leaveEvent(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $event = $this->findEvent($id);
        if (!$event instanceof Event) {
            return $this->json(['message' => 'Event not found.'], 404);
        }

        if ($event->getCreator()->getId()->equals($user->getId())) {
            return $this->json(['message' => 'Event organizer cannot leave the event.'], 422);
        }

        $membership = $this->getEventMembershipRepository()->findByUserAndEvent($user, $event);
        if ($membership instanceof EventMembership) {
            $this->entityManager->remove($membership);
            $this->entityManager->flush();
        }

        return $this->json(['event' => $this->formatter->event($event, $user)]);
    }

    #[Route('/events/{id}/members', name: 'api_events_members', methods: ['GET'])]
    public function getEventMembers(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $event = $this->findEvent($id);
        if (!$event instanceof Event) {
            return $this->json(['message' => 'Event not found.'], 404);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $role = trim((string) $request->query->get('role', ''));
        $memberships = $role !== ''
            ? $this->getEventMembershipRepository()->findByRole($event, $role, $limit)
            : $this->getEventMembershipRepository()->findByEvent($event, $limit);

        return $this->json([
            'members' => array_map(fn (EventMembership $membership): array => $this->formatter->eventMembership($membership, $user), $memberships),
        ]);
    }

    #[Route('/events/{id}/members/{userId}', name: 'api_events_member_update', methods: ['PUT'])]
    public function updateMemberRole(string $id, string $userId, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $event = $this->findEvent($id);
        if (!$event instanceof Event) {
            return $this->json(['message' => 'Event not found.'], 404);
        }

        if (!$event->hasPermission($user, 'admin')) {
            return $this->json(['message' => 'Insufficient permissions.'], 403);
        }

        $payload = $this->combineRequestData($request);
        $role = (string) ($payload['role'] ?? '');
        if (!in_array($role, [Event::ROLE_ORGANIZER, Event::ROLE_COORGANIZER, Event::ROLE_ATTENDEE, Event::ROLE_SPEAKER], true)) {
            return $this->json(['message' => 'Invalid event role.'], 422);
        }

        $targetUser = $this->resolveUser($userId);
        if (!$targetUser instanceof User) {
            return $this->json(['message' => 'User not found.'], 404);
        }

        if ($targetUser->getId()->equals($event->getCreator()->getId()) && $role !== Event::ROLE_ORGANIZER) {
            return $this->json(['message' => 'Event organizer must remain organizer.'], 422);
        }

        $membership = $this->getEventMembershipRepository()->findByUserAndEvent($targetUser, $event);
        if (!$membership instanceof EventMembership) {
            return $this->json(['message' => 'Membership not found.'], 404);
        }

        if ($membership->isOrganizer() && $role !== Event::ROLE_ORGANIZER) {
            $organizerCount = $this->getEventMembershipRepository()->countMembersByRole($event, Event::ROLE_ORGANIZER);
            if ($organizerCount <= 1) {
                return $this->json(['message' => 'Cannot demote the last organizer.'], 422);
            }
        }

        $membership->setRole($role);
        $this->entityManager->flush();

        return $this->json(['membership' => $this->formatter->eventMembership($membership, $user)]);
    }

    #[Route('/events/{id}/members/{userId}', name: 'api_events_member_remove', methods: ['DELETE'])]
    public function removeMember(string $id, string $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $event = $this->findEvent($id);
        if (!$event instanceof Event) {
            return $this->json(['message' => 'Event not found.'], 404);
        }

        if (!$event->hasPermission($user, 'admin')) {
            return $this->json(['message' => 'Insufficient permissions.'], 403);
        }

        $targetUser = $this->resolveUser($userId);
        if (!$targetUser instanceof User) {
            return $this->json(['message' => 'User not found.'], 404);
        }

        if ($targetUser->getId()->equals($event->getCreator()->getId())) {
            return $this->json(['message' => 'Event organizer cannot be removed.'], 422);
        }

        $membership = $this->getEventMembershipRepository()->findByUserAndEvent($targetUser, $event);
        if (!$membership instanceof EventMembership) {
            return $this->json(['message' => 'Membership not found.'], 404);
        }

        if ($membership->isOrganizer()) {
            $organizerCount = $this->getEventMembershipRepository()->countMembersByRole($event, Event::ROLE_ORGANIZER);
            if ($organizerCount <= 1) {
                return $this->json(['message' => 'Cannot remove the last organizer.'], 422);
            }
        }

        $this->entityManager->remove($membership);
        $this->entityManager->flush();

        return $this->json(['message' => 'Member removed successfully.']);
    }

    #[Route('/events/{id}/posts', name: 'api_events_posts_list', methods: ['GET'])]
    public function listPosts(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $event = $this->findEvent($id);
        if (!$event instanceof Event) {
            return $this->json(['message' => 'Event not found.'], 404);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $posts = $this->getEventPostRepository()->findByEvent($event, $user, $limit);

        return $this->json([
            'posts' => array_map(fn (EventPost $post): array => $this->formatter->eventPost($post, $user), $posts),
        ]);
    }

    #[Route('/events/{id}/posts', name: 'api_events_posts_create', methods: ['POST'])]
    public function createPost(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $event = $this->findEvent($id);
        if (!$event instanceof Event) {
            return $this->json(['message' => 'Event not found.'], 404);
        }

        $payload = $this->combineRequestData($request);
        $content = trim((string) ($payload['content'] ?? ''));
        if ($content === '') {
            return $this->json(['message' => 'Post content is required.'], 422);
        }

        if (!$event->hasPermission($user, 'post')) {
            return $this->json(['message' => 'Insufficient permissions.'], 403);
        }

        $post = new EventPost();
        $post
            ->setEvent($event)
            ->setAuthor($user)
            ->setContent($content)
            ->setHashtags($this->extractHashtags($content));

        $image = $request->files->get('image');
        if ($image instanceof UploadedFile) {
            $path = $this->storeImage($image, 'event-posts');
            if ($path === null) {
                return $this->json(['message' => 'Invalid image upload.'], 422);
            }
            $post->setImagePath($path);
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json(['post' => $this->formatter->eventPost($post, $user)], 201);
    }

    private function normalizeEventSettings(mixed $settings): array
    {
        $incoming = [];
        if (is_string($settings) && $settings !== '') {
            $decoded = json_decode($settings, true);
            if (is_array($decoded)) {
                $incoming = $decoded;
            }
        } elseif (is_array($settings)) {
            $incoming = $settings;
        }

        return array_merge([
            'allow_public_posts' => true,
            'require_approval' => false,
            'enable_discussion' => true,
            'send_reminders' => true,
        ], array_intersect_key($incoming, array_flip([
            'allow_public_posts',
            'require_approval',
            'enable_discussion',
            'send_reminders',
        ])));
    }

    private function storeImage(UploadedFile $file, string $folder): ?string
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

        $uploadDir = sprintf('%s/public/uploads/%s', (string) $this->getParameter('kernel.project_dir'), $folder);
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadDir));
        }

        $name = Uuid::v7()->toRfc4122() . '.' . $extension;
        $file->move($uploadDir, $name);

        return sprintf('/uploads/%s/%s', $folder, $name);
    }

    private function extractHashtags(string $content): array
    {
        preg_match_all('/(?:^|\s)#([\p{L}\p{N}_-]{2,50})/u', $content, $matches);

        return array_values(array_unique(array_map(static fn (string $tag): string => mb_strtolower($tag), $matches[1] ?? [])));
    }

    private function resolveUser(string $userId): ?User
    {
        try {
            return $this->entityManager->find(User::class, Uuid::fromString($userId));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function findEvent(string $id): ?Event
    {
        try {
            return $this->entityManager->find(Event::class, Uuid::fromString($id));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getEventRepository(): EventRepository
    {
        $repository = $this->entityManager->getRepository(Event::class);
        if (!$repository instanceof EventRepository) {
            throw new \LogicException('Event repository is not configured correctly.');
        }

        return $repository;
    }

    private function getEventMembershipRepository(): EventMembershipRepository
    {
        $repository = $this->entityManager->getRepository(EventMembership::class);
        if (!$repository instanceof EventMembershipRepository) {
            throw new \LogicException('EventMembership repository is not configured correctly.');
        }

        return $repository;
    }

    private function getEventPostRepository(): EventPostRepository
    {
        $repository = $this->entityManager->getRepository(EventPost::class);
        if (!$repository instanceof EventPostRepository) {
            throw new \LogicException('EventPost repository is not configured correctly.');
        }

        return $repository;
    }
}


