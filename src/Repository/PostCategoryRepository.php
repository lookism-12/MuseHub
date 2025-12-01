<?php

namespace App\Repository;

use App\Entity\PostCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostCategory>
 */
class PostCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostCategory::class);
    }

    /**
     * Find category by slug
     */
    public function findBySlug(string $slug): ?PostCategory
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Get post count statistics by category
     */
    public function getPostCountByCategory(): array
    {
        $result = $this->createQueryBuilder('pc')
            ->select('pc.id', 'pc.name', 'pc.slug', 'COUNT(p.id) as post_count')
            ->leftJoin('pc.posts', 'p')
            ->groupBy('pc.id')
            ->orderBy('post_count', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
