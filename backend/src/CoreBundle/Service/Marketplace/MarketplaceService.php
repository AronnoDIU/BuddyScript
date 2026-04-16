<?php

declare(strict_types=1);

namespace CoreBundle\Service\Marketplace;

use CoreBundle\Entity\Marketplace\Listing;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Marketplace\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

class MarketplaceService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function list(string $query, ?string $category, int $limit, int $offset): array
    {
        $safeLimit = max(1, min(50, $limit));
        $safeOffset = max(0, $offset);

        $items = $this->getListingRepository()->findMarketplaceFeed($query, $category, $safeLimit, $safeOffset);

        return [
            'listings' => array_map(fn (Listing $listing): array => $this->formatListing($listing), $items),
            'query' => trim($query),
            'category' => $category,
            'offset' => $safeOffset,
            'limit' => $safeLimit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function myListings(User $seller): array
    {
        return [
            'listings' => array_map(
                fn (Listing $listing): array => $this->formatListing($listing),
                $this->getListingRepository()->findBySeller($seller, 200)
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function create(User $seller, array $payload, ?UploadedFile $image): array
    {
        $listing = new Listing();
        $listing
            ->setSeller($seller)
            ->setTitle((string) $payload['title'])
            ->setDescription((string) $payload['description'])
            ->setPriceAmount((int) ($payload['priceAmount'] ?? 0))
            ->setCurrency((string) ($payload['currency'] ?? 'USD'))
            ->setCategory((string) ($payload['category'] ?? 'general'))
            ->setConditionType((string) ($payload['conditionType'] ?? 'used'))
            ->setLocation(isset($payload['location']) ? (string) $payload['location'] : null)
            ->setTags($this->extractTags((string) ($payload['tags'] ?? '')))
            ->setStatus((string) ($payload['status'] ?? Listing::STATUS_ACTIVE));

        if ($image instanceof UploadedFile) {
            $path = $this->storeImage($image);
            if ($path === null) {
                throw new \InvalidArgumentException('Invalid listing image upload.');
            }
            $listing->setImagePath($path);
        }

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        return ['listing' => $this->formatListing($listing)];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(string $id): ?array
    {
        $listing = $this->getListingRepository()->findById($id);
        if (!$listing instanceof Listing) {
            return null;
        }

        return ['listing' => $this->formatListing($listing)];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function update(User $seller, string $id, array $payload, ?UploadedFile $image): ?array
    {
        $listing = $this->getListingRepository()->findById($id);
        if (!$listing instanceof Listing) {
            return null;
        }

        if (!$listing->getSeller()->getId()->equals($seller->getId())) {
            return null;
        }

        if (array_key_exists('title', $payload)) {
            $listing->setTitle((string) $payload['title']);
        }
        if (array_key_exists('description', $payload)) {
            $listing->setDescription((string) $payload['description']);
        }
        if (array_key_exists('priceAmount', $payload)) {
            $listing->setPriceAmount((int) $payload['priceAmount']);
        }
        if (array_key_exists('currency', $payload)) {
            $listing->setCurrency((string) $payload['currency']);
        }
        if (array_key_exists('category', $payload)) {
            $listing->setCategory((string) $payload['category']);
        }
        if (array_key_exists('conditionType', $payload)) {
            $listing->setConditionType((string) $payload['conditionType']);
        }
        if (array_key_exists('location', $payload)) {
            $listing->setLocation($payload['location'] !== null ? (string) $payload['location'] : null);
        }
        if (array_key_exists('tags', $payload)) {
            $listing->setTags($this->extractTags((string) ($payload['tags'] ?? '')));
        }
        if (array_key_exists('status', $payload)) {
            $listing->setStatus((string) $payload['status']);
        }

        if ($image instanceof UploadedFile) {
            $path = $this->storeImage($image);
            if ($path === null) {
                throw new \InvalidArgumentException('Invalid listing image upload.');
            }
            $listing->setImagePath($path);
        }

        $this->entityManager->flush();

        return ['listing' => $this->formatListing($listing)];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function markSold(User $seller, string $id): ?array
    {
        $listing = $this->getListingRepository()->findById($id);
        if (!$listing instanceof Listing) {
            return null;
        }

        if (!$listing->getSeller()->getId()->equals($seller->getId())) {
            return null;
        }

        $listing->setStatus(Listing::STATUS_SOLD);
        $this->entityManager->flush();

        return ['listing' => $this->formatListing($listing)];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function delete(User $seller, string $id): ?array
    {
        $listing = $this->getListingRepository()->findById($id);
        if (!$listing instanceof Listing) {
            return null;
        }

        if (!$listing->getSeller()->getId()->equals($seller->getId())) {
            return null;
        }

        $this->entityManager->remove($listing);
        $this->entityManager->flush();

        return ['message' => 'Listing deleted successfully.'];
    }

    private function storeImage(UploadedFile $file): ?string
    {
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false || !isset($imageInfo[2])) {
            return null;
        }

        $extensionMap = [
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];

        $extension = $extensionMap[$imageInfo[2]] ?? null;
        if ($extension === null) {
            return null;
        }

        if ($file->getSize() !== null && $file->getSize() > 7 * 1024 * 1024) {
            return null;
        }

        $uploadDir = $this->projectDir . '/public/uploads/marketplace';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadDir));
        }

        $name = Uuid::v7()->toRfc4122() . '.' . $extension;
        $file->move($uploadDir, $name);

        return '/uploads/marketplace/' . $name;
    }

    /**
     * @return list<string>
     */
    private function extractTags(string $tags): array
    {
        $parts = preg_split('/[,\s]+/', mb_strtolower(trim($tags))) ?: [];

        $clean = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $clean[] = $part;
        }

        return array_values(array_unique($clean));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatListing(Listing $listing): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $imageUrl = $listing->getImagePath();
        if ($imageUrl !== null && $request !== null && preg_match('#^https?://#i', $imageUrl) !== 1) {
            $imageUrl = $request->getSchemeAndHttpHost() . $imageUrl;
        }

        return [
            'id' => $listing->getId()->toRfc4122(),
            'title' => $listing->getTitle(),
            'description' => $listing->getDescription(),
            'priceAmount' => $listing->getPriceAmount(),
            'currency' => $listing->getCurrency(),
            'category' => $listing->getCategory(),
            'conditionType' => $listing->getConditionType(),
            'imageUrl' => $imageUrl,
            'location' => $listing->getLocation(),
            'status' => $listing->getStatus(),
            'tags' => $listing->getTags(),
            'createdAt' => $listing->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $listing->getUpdatedAt()?->format(DATE_ATOM),
            'seller' => [
                'id' => $listing->getSeller()->getId()->toRfc4122(),
                'displayName' => $listing->getSeller()->getDisplayName(),
                'avatarUrl' => sprintf('https://www.gravatar.com/avatar/%s?d=identicon&s=128', md5(mb_strtolower(trim($listing->getSeller()->getEmail())))),
            ],
        ];
    }

    private function getListingRepository(): ListingRepository
    {
        $repository = $this->entityManager->getRepository(Listing::class);
        if (!$repository instanceof ListingRepository) {
            throw new \LogicException('Marketplace listing repository is not configured correctly.');
        }

        return $repository;
    }
}

