<?php

namespace App\Repository;

use App\Entity\Artwork;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artwork>
 */
class ArtworkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artwork::class);
    }

    public function search(?int $categoryId, ?string $artistUuid, ?float $minPrice, ?float $maxPrice): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($categoryId !== null) {
            $qb->andWhere('a.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        if ($artistUuid !== null) {
            $qb->andWhere('a.artistUuid = :artistUuid')
               ->setParameter('artistUuid', $artistUuid);
        }

        if ($minPrice !== null) {
            $qb->andWhere('a.price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('a.price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        return $qb
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Enhanced search with filtering and sorting
     * 
     * @param array $filters Array containing filter parameters
     * @param string|null $sortBy Sort field and direction
     * @return array
     */
    public function findWithFiltersAndSort(array $filters = [], ?string $sortBy = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c');

        // Filter by status (default to visible)
        if (isset($filters['status'])) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $filters['status']);
        } else {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', 'visible');
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $qb->andWhere('a.category = :category')
               ->setParameter('category', $filters['category']);
        }

        // Filter by price range
        if (!empty($filters['minPrice'])) {
            $qb->andWhere('a.price >= :minPrice')
               ->andWhere('a.price IS NOT NULL')
               ->setParameter('minPrice', $filters['minPrice']);
        }

        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('a.price <= :maxPrice')
               ->andWhere('a.price IS NOT NULL')
               ->setParameter('maxPrice', $filters['maxPrice']);
        }

        // Filter by date range
        if (!empty($filters['startDate'])) {
            $qb->andWhere('a.createdAt >= :startDate')
               ->setParameter('startDate', new \DateTime($filters['startDate']));
        }

        if (!empty($filters['endDate'])) {
            $endDate = new \DateTime($filters['endDate']);
            $endDate->setTime(23, 59, 59); // Include the entire end date
            $qb->andWhere('a.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'price_asc':
                // NULL prices at the end
                $qb->addSelect('CASE WHEN a.price IS NULL THEN 1 ELSE 0 END as HIDDEN price_null_flag')
                   ->orderBy('price_null_flag', 'ASC')
                   ->addOrderBy('a.price', 'ASC');
                break;
            case 'price_desc':
                // NULL prices at the end
                $qb->addSelect('CASE WHEN a.price IS NULL THEN 1 ELSE 0 END as HIDDEN price_null_flag')
                   ->orderBy('price_null_flag', 'ASC')
                   ->addOrderBy('a.price', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('a.createdAt', 'ASC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('a.createdAt', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get price range for all visible artworks
     * 
     * @return array
     */
    public function getPriceRange(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('MIN(a.price) as minPrice, MAX(a.price) as maxPrice')
            ->where('a.status = :status')
            ->andWhere('a.price IS NOT NULL')
            ->setParameter('status', 'visible');

        $result = $qb->getQuery()->getSingleResult();
        
        return [
            'min' => $result['minPrice'] ?? 0,
            'max' => $result['maxPrice'] ?? 10000
        ];
    }
}
