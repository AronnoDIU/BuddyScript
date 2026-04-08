<?php

namespace ApiBundle\Validation;

use CoreBundle\Entity\SocialGraph\Connection;
use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\Blank;
use Respect\Validation\Validators\In;
use Respect\Validation\Validators\Not;
use Respect\Validation\Validators\StringType;

class SocialGraphValidator extends AbstractValidator
{
    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('SocialGraph validator has no action defined');
        }

        $this->rules = [];

        switch ($this->action) {
            case 'send_request':
                $this->rules['targetUserId'] = new AllOf(new StringType(), new Not(new Blank()));
                break;
            case 'respond_request':
                $this->rules['id'] = new AllOf(new StringType(), new Not(new Blank()));
                $this->rules['status'] = new In([Connection::STATUS_ACCEPTED, Connection::STATUS_REJECTED]);
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported social graph validator action: %s', $this->action));
        }

        return $this->rules;
    }
}

