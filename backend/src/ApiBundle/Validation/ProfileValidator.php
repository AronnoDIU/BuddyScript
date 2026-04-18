<?php

namespace ApiBundle\Validation;

use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\Blank;
use Respect\Validation\Validators\Not;
use Respect\Validation\Validators\StringType;

class ProfileValidator extends AbstractValidator
{
    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('Profile validator has no action defined');
        }

        $this->rules = [];

        switch ($this->action) {
            case 'show':
                $this->rules['id'] = new AllOf(new StringType(), new Not(new Blank()));
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported profile validator action: %s', $this->action));
        }

        return $this->rules;
    }
}
