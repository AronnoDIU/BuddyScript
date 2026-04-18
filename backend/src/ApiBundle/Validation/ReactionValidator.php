<?php

namespace ApiBundle\Validation;

use CoreBundle\Service\Reaction as ReactionService;
use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\ArrayType;
use Respect\Validation\Validators\Each;
use Respect\Validation\Validators\In;
use Respect\Validation\Validators\Key;
use Respect\Validation\Validators\KeySet;
use Respect\Validation\Validators\Not;
use Respect\Validation\Validators\Regex;
use Respect\Validation\Validators\StringType;

class ReactionValidator extends AbstractValidator
{
    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('Reaction validator has no action defined');
        }

        $this->rules = [];

        switch ($this->action) {
            case 'toggle_reaction':
                $this->rules['targetType'] = new In(['post', 'comment', 'reply']);
                $this->rules['targetId'] = new AllOf(new StringType(), new Regex('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'));
                $this->rules['type'] = new In(ReactionService::reactionTypes());
                break;
            case 'target_reactions':
                $this->rules['targetType'] = new In(['post', 'comment', 'reply']);
                $this->rules['targetId'] = new AllOf(new StringType(), new Regex('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'));
                break;
            case 'batch_target_reactions':
                $this->rules['targets'] = new AllOf(
                    new ArrayType(),
                    new Each(
                        new KeySet(
                            new Key('targetType', new In(['post', 'comment', 'reply'])),
                            new Key('targetId', new AllOf(new StringType(), new Regex('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'))),
                        )
                    )
                );
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported reaction validator action: %s', $this->action));
        }

        return $this->rules;
    }
}
