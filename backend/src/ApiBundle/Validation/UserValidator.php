<?php

namespace ApiBundle\Validation;

use ApiBundle\Validation\Traits\EntityValidatorTrait;
use CoreBundle\Entity\User as UserEntity;
use Respect\Validation\Validator as V;

/**
 * Class UserValidator
 */
class UserValidator extends AbstractValidator
{
    use EntityValidatorTrait;

    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('User validator has no action defined');
        }

        switch ($this->action) {
            case 'list':
                $this->rules['id'] = V::optional(V::notBlank()->intVal())->setName('Id');
                $this->rules['app_id'] = V::optional(V::notBlank()->intVal())->setName('App id');
                $this->rules['user_name'] = V::optional(V::notBlank()->stringType()->setName('Name'));
                $this->rules['email'] = V::optional(V::notBlank()->stringType()->setName('Email'));
                $this->rules['phone'] = V::optional(V::notBlank()->stringType()->setName('Phone'));
                $this->rules['active'] = V::optional(V::notBlank()->boolVal())->setName('Active');
                break;
            case 'change_status':
                $this->rules['id'] = $this->entityExists(UserEntity::class)->setName('Id');
                $this->rules['enabled'] = V::boolVal()->setName('Enabled');
                break;
            default:
                break;
        }

        return $this->rules;
    }
}
