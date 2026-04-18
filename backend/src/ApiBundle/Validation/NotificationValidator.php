<?php

namespace ApiBundle\Validation;

use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\Blank;
use Respect\Validation\Validators\Not;
use Respect\Validation\Validators\StringType;

class NotificationValidator extends AbstractValidator
{
    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('Notification validator has no action defined');
        }

        $this->rules = [];

        switch ($this->action) {
            case 'mark_read':
                $this->rules['id'] = new AllOf(new StringType(), new Not(new Blank()));
                break;
            case 'mark_all_read':
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported notification validator action: %s', $this->action));
        }

        return $this->rules;
    }
}
