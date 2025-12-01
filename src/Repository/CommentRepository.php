<?php
namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }


    /**
     * Find only root-level comments (no parent) for a post
     * @return Comment[]
     */
    public function findRootCommentsByPost(Post $post): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.post = :post')
            ->andWhere('c.parentComment IS NULL')
            ->setParameter('post', $post)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find replies to a specific comment
     * @return Comment[]
     */
    public function findRepliesByParent(Comment $parent): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parentComment = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Build a hierarchical tree of comments for a post
     * @return array
     */
    public function getCommentTree(Post $post): array
    {
        $rootComments = $this->findRootCommentsByPost($post);

        $tree = [];
        foreach ($rootComments as $comment) {
            $tree[] = $this->buildCommentNode($comment);
        }

        return $tree;
    }

    /**
     * Recursively build comment node with replies
     */
    private function buildCommentNode(Comment $comment): array
    {
        $node = [
            'comment' => $comment,
            'replies' => []
        ];

        foreach ($comment->getReplies() as $reply) {
            $node['replies'][] = $this->buildCommentNode($reply);
        }

        return $node;
    }
}
