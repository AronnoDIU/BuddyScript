<?php

namespace ApiBundle\Validation;

use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\ArrayType;
use Respect\Validation\Validators\Blank;
use Respect\Validation\Validators\Not;
use Respect\Validation\Validators\StringType;

class MessengerValidator extends AbstractValidator
{
    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('Messenger validator has no action defined');
        }

        $this->rules = [];

        switch ($this->action) {
            case 'messages':
            case 'mark_read':
            case 'pin':
            case 'mute':
            case 'archive':
                $this->rules['id'] = new AllOf(new StringType(), new Not(new Blank()));
                break;
            case 'send_message':
                break;
            case 'updates':
                break;
            case 'conversation_list':
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported messenger validator action: %s', $this->action));
        }

        return $this->rules;
    }
}

