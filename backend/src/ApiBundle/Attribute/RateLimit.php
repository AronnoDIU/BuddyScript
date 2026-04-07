<?php

namespace ApiBundle\Attribute;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Class RateLimit
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class RateLimit extends ConfigurationAnnotation
{
    private int $limit;

    private int $period;

    public function __construct(int $limit = -1, int $period = 3600)
    {
        $this->limit = $limit;
        $this->period = $period;
    }

    public function getAliasName(): string
    {
        return 'x-rate-limit';
    }

    public function allowArray(): bool
    {
        return true;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function setPeriod(int $period): self
    {
        $this->period = $period;

        return $this;
    }
}
