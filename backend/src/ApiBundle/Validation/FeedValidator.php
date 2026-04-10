<?php

namespace ApiBundle\Validation;

use CoreBundle\Entity\Post;
use Respect\Validation\Validators\AllOf;
use Respect\Validation\Validators\Blank;
use Respect\Validation\Validators\In;
use Respect\Validation\Validators\IntVal;
use Respect\Validation\Validators\Not;
use Respect\Validation\Validators\Positive;
use Respect\Validation\Validators\StringType;

class FeedValidator extends AbstractValidator
{
    public function getValidationRules(): array
    {
        if (!$this->action) {
            throw new \RuntimeException('Feed validator has no action defined');
        }

        $this->rules = [];

        switch ($this->action) {
            case 'create_post':
                $this->rules['content'] = new AllOf(new StringType(), new Not(new Blank()));
                $this->rules['visibility'] = new In([Post::VISIBILITY_PUBLIC, Post::VISIBILITY_PRIVATE]);
                break;
            case 'add_comment':
            case 'add_reply':
                $this->rules['id'] = new AllOf(new StringType(), new Not(new Blank()));
                $this->rules['content'] = new AllOf(new StringType(), new Not(new Blank()));
                break;
            case 'toggle_post_like':
            case 'toggle_comment_like':
            case 'post_likes':
            case 'comment_likes':
                $this->rules['id'] = new AllOf(new StringType(), new Not(new Blank()));
                break;
            case 'discovery':
            case 'discovery_topics':
                $this->rules['limit'] = new AllOf(new IntVal(), new Positive());
                break;
            case 'discovery_search':
                $this->rules['q'] = new AllOf(new StringType(), new Not(new Blank()));
                $this->rules['limit'] = new AllOf(new IntVal(), new Positive());
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported feed validator action: %s', $this->action));
        }

        return $this->rules;
    }
}

