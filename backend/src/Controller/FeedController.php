<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\CommentLike;
use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\User;
use App\Repository\CommentLikeRepository;
use App\Repository\CommentRepository;
use App\Repository\PostLikeRepository;
use App\Repository\PostRepository;
use App\Service\ApiFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class FeedController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly PostLikeRepository $postLikeRepository,
        private readonly CommentLikeRepository $commentLikeRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiFormatter $formatter,
        private readonly ValidatorInterface $validator,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/feed', name: 'api_feed', methods: ['GET'])]
    public function feed(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $posts = $this->postRepository->findFeedForUser($user);

        return $this->json([
            'posts' => array_map(fn (Post $post): array => $this->formatter->post($post, $user), $posts),
        ]);
    }

    #[Route('/posts', name: 'api_post_create', methods: ['POST'])]
    public function createPost(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $content = trim((string) $request->request->get('content', ''));
        $visibility = (string) $request->request->get('visibility', Post::VISIBILITY_PUBLIC);

        $errors = $this->validator->validate(
            ['content' => $content, 'visibility' => $visibility],
            new Assert\Collection([
                'content' => [new Assert\Required([new Assert\NotBlank(), new Assert\Length(max: 5000)])],
                'visibility' => [new Assert\Required([new Assert\Choice([Post::VISIBILITY_PUBLIC, Post::VISIBILITY_PRIVATE])])],
            ])
        );

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 422);
        }

        $post = (new Post())
            ->setAuthor($user)
            ->setContent($content)
            ->setVisibility($visibility);

        $image = $request->files->get('image');
        if ($image instanceof UploadedFile) {
            $path = $this->storePostImage($image);
            if ($path === null) {
                return $this->json(['message' => 'Invalid image upload.'], 422);
            }
            $post->setImagePath($path);
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json(['post' => $this->formatter->post($post, $user)], 201);
    }

    #[Route('/posts/{id}/comments', name: 'api_post_comment_create', methods: ['POST'])]
    public function addComment(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $post = $this->postRepository->find($id);
        if (!$post instanceof Post || !$this->canAccessPost($post, $user)) {
            return $this->json(['message' => 'Post not found.'], 404);
        }

        $content = trim((string) ((json_decode($request->getContent(), true) ?? [])['content'] ?? ''));
        if ($content === '') {
            return $this->json(['message' => 'Comment is required.'], 422);
        }

        $comment = (new Comment())
            ->setPost($post)
            ->setAuthor($user)
            ->setContent($content);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->json(['comment' => $this->formatter->comment($comment, $user)], 201);
    }

    #[Route('/comments/{id}/replies', name: 'api_comment_reply_create', methods: ['POST'])]
    public function addReply(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $parent = $this->commentRepository->find($id);
        if (!$parent instanceof Comment) {
            return $this->json(['message' => 'Comment not found.'], 404);
        }

        $post = $parent->getPost();
        if (!$this->canAccessPost($post, $user)) {
            return $this->json(['message' => 'Comment not found.'], 404);
        }

        $content = trim((string) ((json_decode($request->getContent(), true) ?? [])['content'] ?? ''));
        if ($content === '') {
            return $this->json(['message' => 'Reply is required.'], 422);
        }

        $reply = (new Comment())
            ->setPost($post)
            ->setParent($parent)
            ->setAuthor($user)
            ->setContent($content);

        $this->entityManager->persist($reply);
        $this->entityManager->flush();

        return $this->json(['reply' => $this->formatter->comment($reply, $user)], 201);
    }

    #[Route('/posts/{id}/likes/toggle', name: 'api_post_like_toggle', methods: ['POST'])]
    public function togglePostLike(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $post = $this->postRepository->find($id);
        if (!$post instanceof Post || !$this->canAccessPost($post, $user)) {
            return $this->json(['message' => 'Post not found.'], 404);
        }

        $like = $this->postLikeRepository->findOneByPostAndUser($post, $user);
        $liked = false;

        if ($like instanceof PostLike) {
            $this->entityManager->remove($like);
        } else {
            $like = (new PostLike())
                ->setPost($post)
                ->setUser($user);
            $this->entityManager->persist($like);
            $liked = true;
        }

        $this->entityManager->flush();

        $likes = $this->postLikeRepository->findBy(['post' => $post], ['createdAt' => 'DESC']);

        return $this->json([
            'liked' => $liked,
            'likes' => array_map(fn (PostLike $postLike): array => $this->formatter->user($postLike->getUser()), $likes),
        ]);
    }

    #[Route('/comments/{id}/likes/toggle', name: 'api_comment_like_toggle', methods: ['POST'])]
    public function toggleCommentLike(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $comment = $this->commentRepository->find($id);
        if (!$comment instanceof Comment || !$this->canAccessPost($comment->getPost(), $user)) {
            return $this->json(['message' => 'Comment not found.'], 404);
        }

        $like = $this->commentLikeRepository->findOneByCommentAndUser($comment, $user);
        $liked = false;

        if ($like instanceof CommentLike) {
            $this->entityManager->remove($like);
        } else {
            $like = (new CommentLike())
                ->setComment($comment)
                ->setUser($user);
            $this->entityManager->persist($like);
            $liked = true;
        }

        $this->entityManager->flush();

        $likes = $this->commentLikeRepository->findBy(['comment' => $comment], ['createdAt' => 'DESC']);

        return $this->json([
            'liked' => $liked,
            'likes' => array_map(fn (CommentLike $commentLike): array => $this->formatter->user($commentLike->getUser()), $likes),
        ]);
    }

    #[Route('/posts/{id}/likes', name: 'api_post_likes', methods: ['GET'])]
    public function postLikes(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $post = $this->postRepository->find($id);
        if (!$post instanceof Post || !$this->canAccessPost($post, $user)) {
            return $this->json(['message' => 'Post not found.'], 404);
        }

        return $this->json([
            'likes' => array_map(
                fn (PostLike $postLike): array => $this->formatter->user($postLike->getUser()),
                $this->postLikeRepository->findBy(['post' => $post], ['createdAt' => 'DESC'])
            ),
        ]);
    }

    #[Route('/comments/{id}/likes', name: 'api_comment_likes', methods: ['GET'])]
    public function commentLikes(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $comment = $this->commentRepository->find($id);
        if (!$comment instanceof Comment || !$this->canAccessPost($comment->getPost(), $user)) {
            return $this->json(['message' => 'Comment not found.'], 404);
        }

        return $this->json([
            'likes' => array_map(
                fn (CommentLike $commentLike): array => $this->formatter->user($commentLike->getUser()),
                $this->commentLikeRepository->findBy(['comment' => $comment], ['createdAt' => 'DESC'])
            ),
        ]);
    }

    private function canAccessPost(Post $post, User $viewer): bool
    {
        return $post->getVisibility() === Post::VISIBILITY_PUBLIC || $post->getAuthor()->getId()->equals($viewer->getId());
    }

    private function storePostImage(UploadedFile $file): ?string
    {
        if (!in_array($file->getMimeType(), ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true)) {
            return null;
        }

        if ($file->getSize() !== null && $file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        $uploadDir = $this->projectDir . '/public/uploads/posts';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $name = Uuid::v7()->toRfc4122() . '.' . ($file->guessExtension() ?: 'jpg');
        $file->move($uploadDir, $name);

        return '/uploads/posts/' . $name;
    }
}


