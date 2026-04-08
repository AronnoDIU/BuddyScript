<?php

namespace ApiBundle\Validation;

use CoreBundle\Service\Reaction as ReactionService;
use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\Blank;
use Respect\Validation\Validators\In;
use Respect\Validation\Validators\Not;
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
                $this->rules['targetId'] = new AllOf(new StringType(), new Not(new Blank()));
                $this->rules['type'] = new In(ReactionService::reactionTypes());
                break;
            case 'target_reactions':
                $this->rules['targetType'] = new In(['post', 'comment', 'reply']);
                $this->rules['targetId'] = new AllOf(new StringType(), new Not(new Blank()));
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported reaction validator action: %s', $this->action));
        }

        return $this->rules;
    }
}

