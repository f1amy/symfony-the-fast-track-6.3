<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Enum\PublishState;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
    public const PAGINATOR_PER_PAGE = 2;
    private const DAYS_BEFORE_REJECTED_REMOVAL = 7;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function countOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()
            ->delete()
            ->getQuery()
            ->execute();
    }

    private function getOldRejectedQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.state = :state_rejected or c.state = :state_spam')
            ->andWhere('c.createdAt < :date')
            ->setParameters([
                'state_rejected' => PublishState::Rejected,
                'state_spam' => PublishState::Spam,
                'date' => new DateTimeImmutable(-self::DAYS_BEFORE_REJECTED_REMOVAL . ' days'),
            ]);
    }

    public function getCommentPaginator(Conference $conference, int $offset): Paginator
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.conference = :conference')
            ->andWhere('c.state = :state')
            ->setParameter('conference', $conference)
            ->setParameter('state', PublishState::Published)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(self::PAGINATOR_PER_PAGE)
            ->setFirstResult($offset)
            ->getQuery();

        return new Paginator($query);
    }
}
