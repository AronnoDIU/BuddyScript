<?php

namespace ApiBundle\Validation;

use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\Blank;
use Respect\Validation\Validators\Email;
use Respect\Validation\Validators\Not;
use Respect\Validation\Validators\Regex;
use Respect\Validation\Validators\StringType;

class AuthValidator extends AbstractValidator
{
    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('Auth validator has no action defined');
        }

        $this->rules = [];

        switch ($this->action) {
            case 'register':
                $this->rules['firstName'] = new AllOf(new StringType(), new Not(new Blank()));
                $this->rules['lastName'] = new AllOf(new StringType(), new Not(new Blank()));
                $this->rules['email'] = new AllOf(new StringType(), new Not(new Blank()), new Email());
                $this->rules['password'] = new AllOf(new StringType(), new Not(new Blank()), new Regex('/^.{8,255}$/s'));
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported auth validator action: %s', $this->action));
        }

        return $this->rules;
    }
}
