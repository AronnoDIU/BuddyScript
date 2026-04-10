<?php

declare(strict_types=1);

namespace ApiBundle\Validation\Community;

use ApiBundle\Validation\Traits\EntityValidatorTrait;
use CoreBundle\Entity\Community\Group;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class GroupValidator
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
            'create_group' => $this->validateCreateGroup($data),
            'list_groups' => $this->validateListGroups($data),
            'list_public_groups' => $this->validateListPublicGroups($data),
            'get_group' => $this->validateGetGroup($data),
            'update_group' => $this->validateUpdateGroup($data),
            'delete_group' => $this->validateDeleteGroup($data),
            'join_group' => $this->validateJoinGroup($data),
            'leave_group' => $this->validateLeaveGroup($data),
            'get_members' => $this->validateGetMembers($data),
            'update_member' => $this->validateUpdateMember($data),
            'remove_member' => $this->validateRemoveMember($data),
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

    private function validateCreateGroup(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(['message' => 'Group name is required.']),
                new Assert\Length([
                    'min' => 3,
                    'max' => 100,
                    'minMessage' => 'Group name must be at least {{ limit }} characters long.',
                    'maxMessage' => 'Group name cannot be longer than {{ limit }} characters.',
                ]),
            ],
            'description' => new Assert\Optional([
                new Assert\Length([
                    'max' => 1000,
                    'maxMessage' => 'Description cannot be longer than {{ limit }} characters.',
                ]),
            ]),
            'visibility' => new Assert\Optional([
                new Assert\Choice([
                    'choices' => [Group::VISIBILITY_PUBLIC, Group::VISIBILITY_PRIVATE, Group::VISIBILITY_SECRET],
                    'message' => 'Invalid visibility option.',
                ]),
            ]),
            'settings' => new Assert\Optional([
                new Assert\Type(['type' => 'array', 'message' => 'Settings must be an array.']),
            ]),
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }

        if (isset($data['settings']) && is_array($data['settings'])) {
            $this->validateSettings($data['settings']);
        }
    }

    private function validateListGroups(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'limit' => [
                new Assert\Type(['type' => 'integer', 'message' => 'Limit must be an integer.']),
                new Assert\Range([
                    'min' => 5,
                    'max' => 50,
                    'notInRangeMessage' => 'Limit must be between {{ min }} and {{ max }}.',
                ]),
            ],
            'q' => new Assert\Optional([
                new Assert\Type(['type' => 'string', 'message' => 'Query must be a string.']),
                new Assert\Length([
                    'max' => 100,
                    'maxMessage' => 'Query cannot be longer than {{ limit }} characters.',
                ]),
            ]),
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }
    }

    private function validateListPublicGroups(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'limit' => [
                new Assert\Type(['type' => 'integer', 'message' => 'Limit must be an integer.']),
                new Assert\Range([
                    'min' => 5,
                    'max' => 50,
                    'notInRangeMessage' => 'Limit must be between {{ min }} and {{ max }}.',
                ]),
            ],
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }
    }

    private function validateGetGroup(array $data): void
    {
        $this->validateUuid($data, 'id');
    }

    private function validateUpdateGroup(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'id' => [
                new Assert\NotBlank(['message' => 'Group ID is required.']),
                new Assert\Uuid(['message' => 'Invalid group ID format.']),
            ],
            'name' => new Assert\Optional([
                new Assert\Length([
                    'min' => 3,
                    'max' => 100,
                    'minMessage' => 'Group name must be at least {{ limit }} characters long.',
                    'maxMessage' => 'Group name cannot be longer than {{ limit }} characters.',
                ]),
            ]),
            'description' => new Assert\Optional([
                new Assert\Length([
                    'max' => 1000,
                    'maxMessage' => 'Description cannot be longer than {{ limit }} characters.',
                ]),
            ]),
            'visibility' => new Assert\Optional([
                new Assert\Choice([
                    'choices' => [Group::VISIBILITY_PUBLIC, Group::VISIBILITY_PRIVATE, Group::VISIBILITY_SECRET],
                    'message' => 'Invalid visibility option.',
                ]),
            ]),
            'settings' => new Assert\Optional([
                new Assert\Type(['type' => 'array', 'message' => 'Settings must be an array.']),
            ]),
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }

        if (isset($data['settings']) && is_array($data['settings'])) {
            $this->validateSettings($data['settings']);
        }
    }

    private function validateJoinGroup(array $data): void
    {
        $this->validateUuid($data, 'id');
    }

    private function validateDeleteGroup(array $data): void
    {
        $this->validateUuid($data, 'id');
    }

    private function validateLeaveGroup(array $data): void
    {
        $this->validateUuid($data, 'id');
    }

    private function validateGetMembers(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'id' => [
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
            'role' => new Assert\Optional([
                new Assert\Choice([
                    'choices' => [Group::ROLE_ADMIN, Group::ROLE_MODERATOR, Group::ROLE_MEMBER, ''],
                    'message' => 'Invalid role option.',
                ]),
            ]),
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }
    }

    private function validateUpdateMember(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'id' => [
                new Assert\NotBlank(['message' => 'Group ID is required.']),
                new Assert\Uuid(['message' => 'Invalid group ID format.']),
            ],
            'userId' => [
                new Assert\NotBlank(['message' => 'User ID is required.']),
                new Assert\Uuid(['message' => 'Invalid user ID format.']),
            ],
            'role' => [
                new Assert\NotBlank(['message' => 'Role is required.']),
                new Assert\Choice([
                    'choices' => [Group::ROLE_ADMIN, Group::ROLE_MODERATOR, Group::ROLE_MEMBER],
                    'message' => 'Invalid role option.',
                ]),
            ],
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }
    }

    private function validateRemoveMember(array $data): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'id' => [
                new Assert\NotBlank(['message' => 'Group ID is required.']),
                new Assert\Uuid(['message' => 'Invalid group ID format.']),
            ],
            'userId' => [
                new Assert\NotBlank(['message' => 'User ID is required.']),
                new Assert\Uuid(['message' => 'Invalid user ID format.']),
            ],
        ]);

        $violations = $validator->validate($data, $constraints);
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }
    }

    private function validateSettings(array $settings): void
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'allow_member_posts' => [
                new Assert\Type(['type' => 'bool', 'message' => 'allow_member_posts must be a boolean.']),
            ],
            'require_approval' => [
                new Assert\Type(['type' => 'bool', 'message' => 'require_approval must be a boolean.']),
            ],
            'enable_discussion' => [
                new Assert\Type(['type' => 'bool', 'message' => 'enable_discussion must be a boolean.']),
            ],
        ]);

        $violations = $validator->validate($settings, $constraints);
        foreach ($violations as $violation) {
            $field = 'settings.' . $violation->getPropertyPath();
            $this->errors[$field] = $violation->getMessage();
        }
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
