<?php

declare(strict_types=1);

namespace ApiBundle\Controller\Community;

use ApiBundle\Controller\BaseController;
use CoreBundle\Entity\Community\Page;
use CoreBundle\Entity\Community\PageMembership;
use CoreBundle\Entity\Community\PagePost;
use CoreBundle\Entity\Community\PagePostComment;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\PageMembershipRepository;
use CoreBundle\Repository\Community\PagePostRepository;
use CoreBundle\Repository\Community\PageRepository;
use CoreBundle\Service\ApiFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

class PageController extends BaseController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiFormatter $formatter,
    ) {
        parent::__construct();
    }

    #[Route('/pages', name: 'api_pages_list', methods: ['GET'])]
    public function listPages(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $query = trim((string) $request->query->get('q', ''));

        if ($query !== '') {
            $pages = $this->getPageRepository()->searchPages($query, $user, $limit);
        } else {
            $pages = $this->getPageRepository()->findPagesForUser($user, $limit);
        }

        return $this->json([
            'pages' => array_map(fn (Page $page): array => $this->formatter->page($page, $user), $pages),
            'query' => $query,
        ]);
    }

    #[Route('/pages/public', name: 'api_pages_public', methods: ['GET'])]
    public function listPublicPages(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $query = trim((string) $request->query->get('q', ''));

        $pages = $query !== ''
            ? array_filter(
                $this->getPageRepository()->findPublicPages($limit * 2),
                fn (Page $page): bool => mb_stripos($page->getName() . ' ' . ($page->getDescription() ?? '') . ' ' . $page->getCategory(), $query) !== false,
            )
            : $this->getPageRepository()->findPublicPages($limit);

        return $this->json([
            'pages' => array_map(fn (Page $page): array => $this->formatter->page($page, $user), array_slice(array_values($pages), 0, $limit)),
        ]);
    }

    #[Route('/pages', name: 'api_pages_create', methods: ['POST'])]
    public function createPage(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->json(['message' => 'Page name is required.'], 422);
        }

        $page = new Page();
        try {
            $page
                ->setName($name)
                ->setDescription(isset($payload['description']) ? (string) $payload['description'] : null)
                ->setCategory($this->normalizePageCategory((string) ($payload['category'] ?? Page::CATEGORY_OTHER)))
                ->setCreator($user)
                ->setSettings($this->normalizePageSettings($payload['settings'] ?? []));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        $avatar = $request->files->get('avatar');
        if ($avatar instanceof UploadedFile) {
            $path = $this->storeImage($avatar, 'pages');
            if ($path === null) {
                return $this->json(['message' => 'Invalid avatar upload.'], 422);
            }
            $page->setAvatarPath($path);
        }

        $this->entityManager->persist($page);

        $membership = new PageMembership();
        $membership
            ->setPage($page)
            ->setUser($user)
            ->setRole(Page::ROLE_ADMIN);
        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        return $this->json([
            'page' => $this->formatter->page($page, $user),
            'membership' => $this->formatter->pageMembership($membership, $user),
        ], 201);
    }

    #[Route('/pages/{id}', name: 'api_pages_get', methods: ['GET'])]
    public function getPage(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $page = $this->findPage($id);
        if (!$page instanceof Page) {
            return $this->json(['message' => 'Page not found.'], 404);
        }

        return $this->json(['page' => $this->formatter->page($page, $user)]);
    }

    #[Route('/pages/{id}/follow', name: 'api_pages_follow', methods: ['POST'])]
    public function followPage(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $page = $this->findPage($id);
        if (!$page instanceof Page) {
            return $this->json(['message' => 'Page not found.'], 404);
        }

        $membership = $this->getPageMembershipRepository()->findByUserAndPage($user, $page);
        if (!$membership instanceof PageMembership) {
            $membership = new PageMembership();
            $membership
                ->setPage($page)
                ->setUser($user)
                ->setRole(Page::ROLE_MEMBER);
            $this->entityManager->persist($membership);
            $this->entityManager->flush();
        }

        return $this->json([
            'page' => $this->formatter->page($page, $user),
            'membership' => $this->formatter->pageMembership($membership, $user),
        ]);
    }

    #[Route('/pages/{id}/unfollow', name: 'api_pages_unfollow', methods: ['POST'])]
    public function unfollowPage(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $page = $this->findPage($id);
        if (!$page instanceof Page) {
            return $this->json(['message' => 'Page not found.'], 404);
        }

        if ($page->getCreator()->getId()->equals($user->getId())) {
            return $this->json(['message' => 'Page owners cannot unfollow their own page.'], 422);
        }

        $membership = $this->getPageMembershipRepository()->findByUserAndPage($user, $page);
        if ($membership instanceof PageMembership) {
            $this->entityManager->remove($membership);
            $this->entityManager->flush();
        }

        return $this->json(['page' => $this->formatter->page($page, $user)]);
    }

    #[Route('/pages/{id}/members', name: 'api_pages_members', methods: ['GET'])]
    public function getPageMembers(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $page = $this->findPage($id);
        if (!$page instanceof Page) {
            return $this->json(['message' => 'Page not found.'], 404);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $role = trim((string) $request->query->get('role', ''));
        $memberships = $role !== ''
            ? $this->getPageMembershipRepository()->findByRole($page, $role, $limit)
            : $this->getPageMembershipRepository()->findByPage($page, $limit);

        return $this->json([
            'members' => array_map(fn (PageMembership $membership): array => $this->formatter->pageMembership($membership, $user), $memberships),
        ]);
    }

    #[Route('/pages/{id}/members/{userId}', name: 'api_pages_member_update', methods: ['PUT'])]
    public function updateMemberRole(string $id, string $userId, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $page = $this->findPage($id);
        if (!$page instanceof Page) {
            return $this->json(['message' => 'Page not found.'], 404);
        }

        if (!$page->hasPermission($user, 'admin')) {
            return $this->json(['message' => 'Insufficient permissions.'], 403);
        }

        $payload = $this->combineRequestData($request);
        $role = (string) ($payload['role'] ?? '');
        if (!in_array($role, [Page::ROLE_ADMIN, Page::ROLE_EDITOR, Page::ROLE_MEMBER], true)) {
            return $this->json(['message' => 'Invalid page role.'], 422);
        }

        $targetUser = $this->resolveUser($userId);
        if (!$targetUser instanceof User) {
            return $this->json(['message' => 'User not found.'], 404);
        }

        if ($targetUser->getId()->equals($page->getCreator()->getId()) && $role !== Page::ROLE_ADMIN) {
            return $this->json(['message' => 'Page owner must remain an admin.'], 422);
        }

        $membership = $this->getPageMembershipRepository()->findByUserAndPage($targetUser, $page);
        if (!$membership instanceof PageMembership) {
            return $this->json(['message' => 'Membership not found.'], 404);
        }

        if ($membership->isAdmin() && $role !== Page::ROLE_ADMIN) {
            $adminCount = $this->getPageMembershipRepository()->countMembersByRole($page, Page::ROLE_ADMIN);
            if ($adminCount <= 1) {
                return $this->json(['message' => 'Cannot demote the last admin.'], 422);
            }
        }

        $membership->setRole($role);
        $this->entityManager->flush();

        return $this->json(['membership' => $this->formatter->pageMembership($membership, $user)]);
    }

    #[Route('/pages/{id}/members/{userId}', name: 'api_pages_member_remove', methods: ['DELETE'])]
    public function removeMember(string $id, string $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $page = $this->findPage($id);
        if (!$page instanceof Page) {
            return $this->json(['message' => 'Page not found.'], 404);
        }

        if (!$page->hasPermission($user, 'admin')) {
            return $this->json(['message' => 'Insufficient permissions.'], 403);
        }

        $targetUser = $this->resolveUser($userId);
        if (!$targetUser instanceof User) {
            return $this->json(['message' => 'User not found.'], 404);
        }

        if ($targetUser->getId()->equals($page->getCreator()->getId())) {
            return $this->json(['message' => 'Page owner cannot be removed.'], 422);
        }

        $membership = $this->getPageMembershipRepository()->findByUserAndPage($targetUser, $page);
        if (!$membership instanceof PageMembership) {
            return $this->json(['message' => 'Membership not found.'], 404);
        }

        if ($membership->isAdmin()) {
            $adminCount = $this->getPageMembershipRepository()->countMembersByRole($page, Page::ROLE_ADMIN);
            if ($adminCount <= 1) {
                return $this->json(['message' => 'Cannot remove the last admin.'], 422);
            }
        }

        $this->entityManager->remove($membership);
        $this->entityManager->flush();

        return $this->json(['message' => 'Member removed successfully.']);
    }

    #[Route('/pages/{id}/posts', name: 'api_pages_posts_list', methods: ['GET'])]
    public function listPosts(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $page = $this->findPage($id);
        if (!$page instanceof Page) {
            return $this->json(['message' => 'Page not found.'], 404);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $query = trim((string) $request->query->get('q', ''));
        $posts = $query !== ''
            ? $this->getPagePostRepository()->searchInPage($page, $user, $query, $limit)
            : $this->getPagePostRepository()->findByPage($page, $user, $limit);

        return $this->json([
            'posts' => array_map(fn (PagePost $post): array => $this->formatter->pagePost($post, $user), $posts),
            'query' => $query,
        ]);
    }

    #[Route('/pages/{id}/posts', name: 'api_pages_posts_create', methods: ['POST'])]
    public function createPost(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $page = $this->findPage($id);
        if (!$page instanceof Page) {
            return $this->json(['message' => 'Page not found.'], 404);
        }

        $payload = $this->combineRequestData($request);
        $content = trim((string) ($payload['content'] ?? ''));
        if ($content === '') {
            return $this->json(['message' => 'Post content is required.'], 422);
        }

        if (!$page->hasPermission($user, 'post')) {
            return $this->json(['message' => 'Insufficient permissions.'], 403);
        }

        $post = new PagePost();
        $post
            ->setPage($page)
            ->setAuthor($user)
            ->setContent($content)
            ->setHashtags($this->extractHashtags($content));

        $image = $request->files->get('image');
        if ($image instanceof UploadedFile) {
            $path = $this->storeImage($image, 'page-posts');
            if ($path === null) {
                return $this->json(['message' => 'Invalid image upload.'], 422);
            }
            $post->setImagePath($path);
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json(['post' => $this->formatter->pagePost($post, $user)], 201);
    }

    private function normalizePageSettings(mixed $settings): array
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
            'allow_public_posts' => false,
            'require_approval' => true,
            'enable_comments' => true,
            'show_member_list' => true,
        ], array_intersect_key($incoming, array_flip([
            'allow_public_posts',
            'require_approval',
            'enable_comments',
            'show_member_list',
        ])));
    }

    private function normalizePageCategory(string $category): string
    {
        return match (mb_strtolower(trim($category))) {
            'business' => Page::CATEGORY_BUSINESS,
            'organization', 'organisation' => Page::CATEGORY_ORGANIZATION,
            'community' => Page::CATEGORY_COMMUNITY,
            'news' => Page::CATEGORY_NEWS,
            'entertainment' => Page::CATEGORY_ENTERTAINMENT,
            'brand' => Page::CATEGORY_BRAND,
            'cause' => Page::CATEGORY_CAUSE,
            default => Page::CATEGORY_OTHER,
        };
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

    private function findPage(string $id): ?Page
    {
        try {
            return $this->entityManager->find(Page::class, Uuid::fromString($id));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getPageRepository(): PageRepository
    {
        $repository = $this->entityManager->getRepository(Page::class);
        if (!$repository instanceof PageRepository) {
            throw new \LogicException('Page repository is not configured correctly.');
        }

        return $repository;
    }

    private function getPageMembershipRepository(): PageMembershipRepository
    {
        $repository = $this->entityManager->getRepository(PageMembership::class);
        if (!$repository instanceof PageMembershipRepository) {
            throw new \LogicException('PageMembership repository is not configured correctly.');
        }

        return $repository;
    }

    private function getPagePostRepository(): PagePostRepository
    {
        $repository = $this->entityManager->getRepository(PagePost::class);
        if (!$repository instanceof PagePostRepository) {
            throw new \LogicException('PagePost repository is not configured correctly.');
        }

        return $repository;
    }
}


