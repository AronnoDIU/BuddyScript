<?php

namespace ApiBundle\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RateLimit
{
    private int $limit;

    private int $period;

    public function __construct(int $limit = -1, int $period = 3600)
    {
        $this->limit = $limit;
        $this->period = $period;
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
