<?php

declare(strict_types=1);

namespace ApiBundle\Validation\Community;

use ApiBundle\Validation\Traits\EntityValidatorTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class GroupPostValidator
{
    use EntityValidatorTrait;

    private ?string $currentAction = null;
    private array $errors = [];

    public function setAction(string $action): self
    {
        $this->currentAction = $action;
        return $this;
    }

    public function validate(array $data): void
    {
        $this->errors = [];

        match ($this->currentAction) {
            'create_post' => $this->validateCreatePost($data),
            'list_posts' => $this->validateListPosts($data),
            'get_post' => $this->validateGetPost($data),
            'delete_post' => $this->validateDeletePost($data),
            'toggle_like' => $this->validateToggleLike($data),
            'add_comment' => $this->validateAddComment($data),
            'toggle_comment_like' => $this->validateToggleCommentLike($data),
            default => $this->errors['action'] = 'Invalid action specified.',
        };

        if (!empty($this->errors)) {
            throw new \ApiBundle\Exception\ValidationException('Validation failed.', $this->errors);
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function validateCreatePost(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'groupId' => [
                new Assert\NotBlank(['message' => 'Group ID is required.']),
                new Assert\Uuid(['message' => 'Invalid group ID format.']),
            ],
            'content' => [
                new Assert\NotBlank(['message' => 'Content is required.']),
                new Assert\Length([
                    'min' => 1,
                    'max' => 2000,
                    'minMessage' => 'Content must be at least {{ limit }} characters long.',
                    'maxMessage' => 'Content cannot be longer than {{ limit }} characters.',
                ]),
            ],
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }
    }

    private function validateListPosts(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'groupId' => [
                new Assert\NotBlank(['message' => 'Group ID is required.']),
                new Assert\Uuid(['message' => 'Invalid group ID format.']),
            ],
            'limit' => [
                new Assert\Type(['type' => 'integer', 'message' => 'Limit must be an integer.']),
                new Assert\Range([
                    'min' => 5,
                    'max' => 50,
                    'notInRangeMessage' => 'Limit must be between {{ min }} and {{ max }}.',
                ]),
            ],
            'query' => [
                new Assert\Type(['type' => 'string', 'message' => 'Query must be a string.']),
                new Assert\Length([
                    'max' => 100,
                    'maxMessage' => 'Query cannot be longer than {{ limit }} characters.',
                ]),
            ],
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }
    }

    private function validateGetPost(array $data): void
    {
        $this->validateUuid($data, 'id');
    }

    private function validateToggleLike(array $data): void
    {
        $this->validateUuid($data, 'id');
    }

    private function validateDeletePost(array $data): void
    {
        $this->validateUuid($data, 'id');
    }

    private function validateAddComment(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'postId' => [
                new Assert\NotBlank(['message' => 'Post ID is required.']),
                new Assert\Uuid(['message' => 'Invalid post ID format.']),
            ],
            'content' => [
                new Assert\NotBlank(['message' => 'Content is required.']),
                new Assert\Length([
                    'min' => 1,
                    'max' => 1000,
                    'minMessage' => 'Content must be at least {{ limit }} characters long.',
                    'maxMessage' => 'Content cannot be longer than {{ limit }} characters.',
                ]),
            ],
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }
    }

    private function validateToggleCommentLike(array $data): void
    {
        $this->validateUuid($data, 'id');
    }

    private function validateUuid(array $data, string $field): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            $field => [
                new Assert\NotBlank(['message' => ucfirst($field) . ' is required.']),
                new Assert\Uuid(['message' => 'Invalid ' . $field . ' format.']),
            ],
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $this->errors[$field] = $violation->getMessage();
        }
    }
}
