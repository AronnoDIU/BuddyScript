<?php

namespace CoreBundle\Entity\Media\Gallery;

use Doctrine\ORM\Mapping as ORM;
use Sonata\MediaBundle\Entity\BaseGalleryItem;

#[ORM\Entity]
#[ORM\Table(name: 'media_gallery_item')]
class Item extends BaseGalleryItem
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
