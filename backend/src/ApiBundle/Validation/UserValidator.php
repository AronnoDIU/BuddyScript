<?php

namespace ApiBundle\Validation;

use ApiBundle\Validation\Traits\EntityValidatorTrait;
use CoreBundle\Entity\User as UserEntity;
use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\Blank;
use Respect\Validation\Validators\BoolVal;
use Respect\Validation\Validators\Email;
use Respect\Validation\Validators\IntVal;
use Respect\Validation\Validators\Not;
use Respect\Validation\Validators\NullType;
use Respect\Validation\Validators\OneOf;
use Respect\Validation\Validators\StringType;

class UserValidator extends AbstractValidator
{
    use EntityValidatorTrait;

    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('User validator has no action defined');
        }

        $this->rules = [];

        switch ($this->action) {
            case 'list':
                $this->rules['id'] = new OneOf(new NullType(), new IntVal());
                $this->rules['app_ids'] = new OneOf(new NullType(), new IntVal());
                $this->rules['user_name'] = new OneOf(
                    new NullType(),
                    new AllOf(new StringType(), new Not(new Blank()))
                );
                $this->rules['email'] = new OneOf(new NullType(), new Email());
                $this->rules['phone'] = new OneOf(
                    new NullType(),
                    new AllOf(new StringType(), new Not(new Blank()))
                );
                $this->rules['enabled'] = new OneOf(new NullType(), new BoolVal());
                break;
            case 'change_status':
                $this->rules['id'] = $this->entityExists(UserEntity::class);
                $this->rules['enabled'] = new BoolVal();
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported user validator action: %s', $this->action));
        }

        return $this->rules;
    }
}
