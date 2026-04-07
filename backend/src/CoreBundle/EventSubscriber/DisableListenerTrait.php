<?php

namespace CoreBundle\EventSubscriber;

trait DisableListenerTrait
{
    private bool $enabled = true;

    public function disable(): void
    {
        $this->enabled = false;
    }
}
