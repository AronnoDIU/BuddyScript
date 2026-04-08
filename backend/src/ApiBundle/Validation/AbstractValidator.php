<?php

namespace ApiBundle\Validation;

use ApiBundle\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Respect\Validation\ValidatorBuilder;

abstract class AbstractValidator
{
    public const string VALIDATION_FAILED = 'Validation failed';

    public const string OPERATOR_EQ = '==';

    public const string OPERATOR_NOT_EQ = '!=';

    protected $action;

    protected array $rules = [];

    protected array $errors = [];

    protected array $messages = [];

    protected array $currentInputs = [];

    abstract public function getValidationRules();

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function setAction($action): static
    {
        $this->action = $action;

        return $this;
    }

    public function validateMultiple(array $data = []): true
    {
        $errors = [];

        foreach ($data as $key => $inputs) {
            try {
                $this->validate([$inputs]);
            } catch (ValidationException $exception) {
                $this->errors = [];
                $errors[$key] = $exception->getErrors();
            }
        }

        $this->errors = $errors;

        return $this->passes();
    }

    public function validate(array $inputs): true
    {
        $this->setCurrentInputs($inputs);

        foreach ($this->getValidationRules() as $rule => $validator) {
            $isDependent = \is_array($validator);
            $value = $inputs[$rule] ?? null;

            if ($isDependent) {
                $dependencyValue = $inputs[$validator['dependency']] ?? null;
                $dependencyValue = \is_string($dependencyValue) ? \sprintf('\'%s\'', $dependencyValue)
                    : $dependencyValue;
                $expectedValue = \is_string($validator['value']) ? \sprintf('\'%s\'', $validator['value'])
                    : $validator['value'];
                $expr = \sprintf('return %s %s %s;', $dependencyValue, $validator['operator'], $expectedValue);

                if ($dependencyValue && eval($expr)) {
                    foreach ($validator['rules'] as $childRuleName => $childValidator) {
                        $childRuleParts = explode('.', (string) $childRuleName);
                        $childRuleKey = $childRuleParts[array_key_last($childRuleParts)];
                        $childValue = $value && \is_array($value) ? $value[$childRuleKey] ?? null : $value;

                        $resultQuery = ValidatorBuilder::init($childValidator)->validate($childValue);
                        if ($resultQuery->hasFailed()) {
                            $this->errors[$childRuleName]['errors'] = $resultQuery->getMessages();
                        }
                    }
                }
            } else {
                $resultQuery = ValidatorBuilder::init($validator)->validate($value);
                if ($resultQuery->hasFailed()) {
                    $this->errors[$rule]['errors'] = $resultQuery->getMessages();
                }
            }
        }

        return $this->passes();
    }

    public function passes(): bool
    {
        $errors = $this->getValidationErrors();

        if (\count($errors) > 0) {
            try {
                $this->throwValidationException($errors);
            } catch (ValidationException $e) {
                $this->errors = $e->getErrors();
                return false;
            }
        }

        return true;
    }

    public function getValidationErrors(): array
    {
        return $this->errors;
    }

    public function getCurrentInputs(): array
    {
        return $this->currentInputs;
    }

    protected function setCurrentInputs(array $currentInputs): static
    {
        $this->currentInputs = $currentInputs;

        return $this;
    }

    protected function throwValidationException($errors): void
    {
        $message = self::VALIDATION_FAILED;

        throw new ValidationException($message, $errors);
    }

}
