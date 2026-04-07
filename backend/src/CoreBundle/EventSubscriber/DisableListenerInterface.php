<?php

namespace CoreBundle\EventSubscriber;

interface DisableListenerInterface
{
    public function disable(): void;
}
