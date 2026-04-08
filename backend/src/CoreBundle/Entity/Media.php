<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sonata\MediaBundle\Entity\BaseMedia;

/**
 * Class Media
 */
#[ORM\Entity]
#[ORM\Table(name: 'media')]
class Media extends BaseMedia
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected int $id;

    public function getId(): ?int
    {
        return $this->id ?? null;
    }
}
