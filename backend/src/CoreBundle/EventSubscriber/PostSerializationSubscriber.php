<?php

namespace CoreBundle\EventSubscriber;

use CoreBundle\Entity\Media as MediaEntity;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use Sonata\MediaBundle\Provider\ImageProviderInterface;
use Sonata\MediaBundle\Provider\Pool as MediaPool;

/**
 * Class PostSerializationSubscriber
 */
class PostSerializationSubscriber implements EventSubscriberInterface
{
    private readonly ImageProviderInterface $imageProvider;

    private readonly MediaPool $mediaPool;

    public function __construct(ImageProviderInterface $imageProvider, MediaPool $mediaPool)
    {
        $this->imageProvider = $imageProvider;
        $this->mediaPool = $mediaPool;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => 'serializer.post_serialize',
                'class' => MediaEntity::class,
                'method' => 'serializeMedia',
            ],
        ];
    }

    public function serializeMedia(ObjectEvent $event)
    {
        /** @var MediaEntity $media */
        $media = $event->getObject();
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $event->getVisitor();
        $formats = $this->mediaPool->getFormatNamesByContext($media->getContext());
        if (\is_array($formats)) {
            foreach (array_keys($formats) as $format) {
                $size = explode('_', $format);
                $url = $this->imageProvider->generatePublicUrl($media, $format);
                $visitor->setData(end($size), $url);
            }
        }
    }
}
