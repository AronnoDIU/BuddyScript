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
use Respect\Validation\Validators\Regex;

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
                $this->rules['content'] = new AllOf(new StringType(), new Not(new Blank()), new Regex('/^.{1,2000}$/s'));
                $this->rules['visibility'] = new In([Post::VISIBILITY_PUBLIC, Post::VISIBILITY_PRIVATE]);
                break;
            case 'feed':
                $this->rules['q'] = new AllOf(new StringType(), new Regex('/^.{0,120}$/s'));
                $this->rules['limit'] = new AllOf(new IntVal(), new Positive());
                break;
            case 'add_comment':
            case 'add_reply':
                $this->rules['id'] = new AllOf(new StringType(), new Not(new Blank()));
                $this->rules['content'] = new AllOf(new StringType(), new Not(new Blank()), new Regex('/^.{1,1000}$/s'));
                break;
            case 'toggle_post_like':
            case 'toggle_comment_like':
            case 'post_likes':
            case 'comment_likes':
            case 'delete_post':
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
