<?php

namespace ApiBundle\Validation;

use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\BoolVal;
use Respect\Validation\Validators\Blank;
use Respect\Validation\Validators\IntVal;
use Respect\Validation\Validators\Not;
use Respect\Validation\Validators\NullType;
use Respect\Validation\Validators\OneOf;
use Respect\Validation\Validators\Regex;
use Respect\Validation\Validators\StringType;

class MessengerValidator extends AbstractValidator
{
    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('Messenger validator has no action defined');
        }

        $this->rules = [];
        $uuidRule = new AllOf(new StringType(), new Regex('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'));

        switch ($this->action) {
            case 'messages':
            case 'mark_read':
            case 'pin':
            case 'mute':
            case 'archive':
                $this->rules['id'] = $uuidRule;
                if ($this->action === 'pin') {
                    $this->rules['pinned'] = new OneOf(new NullType(), new BoolVal());
                }
                if ($this->action === 'mute') {
                    $this->rules['minutes'] = new OneOf(new NullType(), new IntVal());
                }
                if ($this->action === 'archive') {
                    $this->rules['archived'] = new OneOf(new NullType(), new BoolVal());
                }
                break;
            case 'send_message':
                $this->rules['conversationId'] = new OneOf(new NullType(), $uuidRule);
                $this->rules['recipientId'] = new OneOf(new NullType(), $uuidRule);
                $this->rules['content'] = new OneOf(new NullType(), new StringType());
                break;
            case 'conversation_list':
            case 'updates':
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported messenger validator action: %s', $this->action));
        }

        return $this->rules;
    }
}
