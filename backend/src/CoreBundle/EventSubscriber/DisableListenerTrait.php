<?php

namespace CoreBundle\EventSubscriber;

/**
 * Trait DisableListenerTrait
 */
trait DisableListenerTrait
{
    private bool $enabled = true;

    public function disable(): void
    {
        $this->enabled = false;
    }
}
