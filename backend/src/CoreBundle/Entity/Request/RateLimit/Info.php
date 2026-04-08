<?php

namespace CoreBundle\Entity\Request\RateLimit;

class Info
{
    private int $limit;
    private int $calls;
    private int $resetTimestamp;

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getCalls(): int
    {
        return $this->calls;
    }

    public function setCalls(int $calls): self
    {
        $this->calls = $calls;

        return $this;
    }

    public function addCall(): self
    {
        ++$this->calls;

        return $this;
    }

    public function getResetTimestamp(): int
    {
        return $this->resetTimestamp;
    }

    public function setResetTimestamp(int $resetTimestamp): self
    {
        $this->resetTimestamp = $resetTimestamp;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'limit' => $this->limit,
            'calls' => $this->calls,
            'reset' => $this->resetTimestamp,
        ];
    }

    public static function fromArray(array $params): self
    {
        $info = new self();
        $info
            ->setCalls($params['calls'])
            ->setLimit($params['limit'])
            ->setResetTimestamp($params['reset']);

        return $info;
    }
}
