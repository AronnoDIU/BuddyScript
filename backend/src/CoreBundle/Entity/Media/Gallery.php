<?php

namespace CoreBundle\Entity\Media;

use Doctrine\ORM\Mapping as ORM;
use Sonata\MediaBundle\Entity\BaseGallery;

#[ORM\Entity]
#[ORM\Table(name: 'media_gallery')]
class Gallery extends BaseGallery
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
