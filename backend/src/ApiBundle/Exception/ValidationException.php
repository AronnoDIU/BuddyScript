<?php

namespace ApiBundle\Exception;

class ValidationException extends \Exception
{
    protected array $errors;

    public function __construct($message, $errors)
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
